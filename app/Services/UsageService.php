<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\UsageAlert;
use App\Models\UsageMetric;
use App\Models\UsagePricing;
use Illuminate\Support\Facades\DB;

/**
 * Usage Service
 *
 * Handles usage tracking, pricing, and alerts for tenant resources.
 */
class UsageService
{
    /**
     * Record a usage metric.
     */
    public function recordMetric(int $tenantId, array $data): UsageMetric
    {
        return UsageMetric::create([
            'tenant_id' => $tenantId,
            'user_id' => $data['user_id'] ?? null,
            'metric_type' => $data['metric_type'],
            'metric_value' => $data['metric_value'],
            'unit' => $data['unit'] ?? 'count',
            'period' => $data['period'] ?? $this->getCurrentPeriod(),
            'metadata' => $data['metadata'] ?? [],
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);
    }

    /**
     * Get current period.
     */
    protected function getCurrentPeriod(): string
    {
        return now()->format('Y-m');
    }

    /**
     * Get usage summary for tenant.
     */
    public function getUsageSummary(int $tenantId, array $filters = []): array
    {
        $query = UsageMetric::where('tenant_id', $tenantId);

        if (! empty($filters['metric_type'])) {
            $query->where('metric_type', $filters['metric_type']);
        }

        if (! empty($filters['period'])) {
            $query->where('period', $filters['period']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('recorded_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('recorded_at', '<=', $filters['to_date']);
        }

        $metrics = $query->get();

        return [
            'total_records' => $metrics->count(),
            'by_type' => $metrics->groupBy('metric_type')
                ->map(fn ($group) => $group->sum('metric_value'))
                ->toArray(),
            'by_period' => $metrics->groupBy('period')
                ->map(fn ($group) => $group->sum('metric_value'))
                ->toArray(),
            'top_users' => $metrics->whereNotNull('user_id')
                ->groupBy('user_id')
                ->map(fn ($group) => [
                    'user_id' => $group->first()->user_id,
                    'total_usage' => $group->sum('metric_value'),
                ])
                ->sortByDesc('total_usage')
                ->take(10)
                ->values()
                ->toArray(),
            'metrics' => $metrics->take(100)->toArray(),
        ];
    }

    /**
     * Get usage for specific metric type.
     */
    public function getUsageByType(int $tenantId, string $metricType, array $filters = []): array
    {
        $query = UsageMetric::where('tenant_id', $tenantId)
            ->where('metric_type', $metricType);

        if (! empty($filters['period'])) {
            $query->where('period', $filters['period']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('recorded_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('recorded_at', '<=', $filters['to_date']);
        }

        $metrics = $query->orderBy('recorded_at', 'desc')->get();

        return [
            'metric_type' => $metricType,
            'total_value' => $metrics->sum('metric_value'),
            'average_value' => $metrics->avg('metric_value'),
            'peak_value' => $metrics->max('metric_value'),
            'records_count' => $metrics->count(),
            'period' => $metrics->first()?->period,
            'data_points' => $metrics->take(100)->toArray(),
        ];
    }

    /**
     * Calculate usage costs based on pricing.
     */
    public function calculateCost(int $tenantId, string $metricType, float $usage): array
    {
        $pricing = UsagePricing::where('tenant_id', $tenantId)
            ->where('metric_type', $metricType)
            ->where('is_active', true)
            ->first();

        if (! $pricing) {
            return [
                'cost' => 0,
                'currency' => 'USD',
                'pricing' => null,
            ];
        }

        $cost = $this->calculatePricingCost($pricing, $usage);

        return [
            'cost' => $cost,
            'currency' => $pricing->currency_code,
            'pricing' => $pricing,
            'usage' => $usage,
        ];
    }

    /**
     * Calculate cost based on pricing tier.
     */
    protected function calculatePricingCost(UsagePricing $pricing, float $usage): float
    {
        return match ($pricing->pricing_model) {
            'tiered' => $this->calculateTieredCost($pricing, $usage),
            'volume' => $this->calculateVolumeCost($pricing, $usage),
            'per_unit' => $usage * $pricing->unit_price,
            'flat_rate' => $pricing->flat_rate,
            default => 0,
        };
    }

    /**
     * Calculate tiered pricing cost.
     */
    protected function calculateTieredCost(UsagePricing $pricing, float $usage): float
    {
        $tiers = $pricing->tiers ?? [];
        $totalCost = 0;
        $remainingUsage = $usage;

        foreach ($tiers as $tier) {
            $from = $tier['from'] ?? 0;
            $to = $tier['to'] ?? PHP_FLOAT_MAX;
            $price = $tier['price'] ?? 0;
            $limit = min($to - $from, $remainingUsage);

            if ($limit <= 0) {
                continue;
            }

            $totalCost += $limit * $price;
            $remainingUsage -= $limit;

            if ($remainingUsage <= 0) {
                break;
            }
        }

        return $totalCost;
    }

    /**
     * Calculate volume pricing cost.
     */
    protected function calculateVolumeCost(UsagePricing $pricing, float $usage): float
    {
        $volumes = $pricing->volumes ?? [];

        foreach ($volumes as $volume) {
            if ($usage >= ($volume['from'] ?? 0) && $usage < ($volume['to'] ?? PHP_FLOAT_MAX)) {
                return $usage * ($volume['price'] ?? 0);
            }
        }

        return $usage * $pricing->unit_price;
    }

    /**
     * Create usage pricing.
     */
    public function createPricing(int $tenantId, array $data): UsagePricing
    {
        return UsagePricing::create([
            'tenant_id' => $tenantId,
            'metric_type' => $data['metric_type'],
            'unit' => $data['unit'] ?? 'count',
            'currency_code' => $data['currency_code'] ?? 'USD',
            'pricing_model' => $data['pricing_model'] ?? 'per_unit',
            'unit_price' => $data['unit_price'] ?? 0,
            'flat_rate' => $data['flat_rate'] ?? 0,
            'tiers' => $data['tiers'] ?? [],
            'volumes' => $data['volumes'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'effective_from' => $data['effective_from'] ?? now(),
            'effective_until' => $data['effective_until'] ?? null,
        ]);
    }

    /**
     * Check for usage alerts.
     */
    public function checkAlerts(int $tenantId): array
    {
        $alerts = UsageAlert::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $triggered = [];

        foreach ($alerts as $alert) {
            $usage = $this->getUsageByType($tenantId, $alert->metric_type, [
                'period' => now()->format('Y-m'),
            ]);

            $shouldTrigger = match ($alert->condition_type) {
                'threshold' => $usage['total_value'] >= $alert->threshold_value,
                'rate' => $this->checkRateAlert($alert, $usage),
                'anomaly' => $this->checkAnomalyAlert($alert, $usage),
                default => false,
            };

            if ($shouldTrigger && now()->diffInHours($alert->last_triggered_at ?? now()) >= 24) {
                $triggered[] = [
                    'alert' => $alert,
                    'current_usage' => $usage['total_value'],
                    'threshold' => $alert->threshold_value,
                ];

                // Send notification
                $this->sendAlertNotification($alert, $usage);

                // Update last triggered time
                $alert->update(['last_triggered_at' => now()]);
            }
        }

        return $triggered;
    }

    /**
     * Check rate-based alert.
     */
    protected function checkRateAlert(UsageAlert $alert, array $usage): bool
    {
        $rate = $usage['total_value'] / max(1, now()->daysInMonth);

        return $rate >= $alert->threshold_value;
    }

    /**
     * Check anomaly alert.
     */
    protected function checkAnomalyAlert(UsageAlert $alert, array $usage): bool
    {
        // Get historical average
        $historical = UsageMetric::where('tenant_id', $alert->tenant_id)
            ->where('metric_type', $alert->metric_type)
            ->where('recorded_at', '>=', now()->subMonths(3))
            ->avg('metric_value') ?? 0;

        $current = $usage['total_value'];
        $deviation = abs($current - $historical) / max(1, $historical);

        return $deviation >= ($alert->threshold_value ?? 2); // 2x deviation by default
    }

    /**
     * Send alert notification.
     */
    protected function sendAlertNotification(UsageAlert $alert, array $usage): void
    {
        SendNotificationJob::dispatch(
            $alert->tenant_id,
            'usage_alert',
            [
                'alert_id' => $alert->id,
                'metric_type' => $alert->metric_type,
                'current_usage' => $usage['total_value'],
                'threshold' => $alert->threshold_value,
            ]
        );
    }

    /**
     * Aggregate usage metrics.
     */
    public function aggregateMetrics(int $tenantId): void
    {
        $period = now()->format('Y-m');

        $aggregated = DB::table('usage_metrics')
            ->where('tenant_id', $tenantId)
            ->where('period', $period)
            ->select([
                'metric_type',
                DB::raw('SUM(metric_value) as total_value'),
                DB::raw('AVG(metric_value) as avg_value'),
                DB::raw('MAX(metric_value) as max_value'),
                DB::raw('MIN(metric_value) as min_value'),
                DB::raw('COUNT(*) as record_count'),
            ])
            ->groupBy('metric_type')
            ->get();

        foreach ($aggregated as $row) {
            DB::table('usage_aggregates')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'metric_type' => $row->metric_type,
                    'period' => $period,
                ],
                [
                    'total_value' => $row->total_value,
                    'avg_value' => $row->avg_value,
                    'max_value' => $row->max_value,
                    'min_value' => $row->min_value,
                    'record_count' => $row->record_count,
                    'aggregated_at' => now(),
                ]
            );
        }
    }

    /**
     * Get usage forecast.
     */
    public function getForecast(int $tenantId, string $metricType, int $months = 3): array
    {
        $historical = DB::table('usage_aggregates')
            ->where('tenant_id', $tenantId)
            ->where('metric_type', $metricType)
            ->orderBy('period', 'desc')
            ->limit(12)
            ->get()
            ->reverse();

        if ($historical->count() < 3) {
            return [];
        }

        // Simple linear regression forecast
        $values = $historical->pluck('total_value')->toArray();
        $n = count($values);
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($values);
        $sumXY = array_sum(array_map(function ($x, $y) {
            return $x * $y;
        }, range(1, $n), $values));
        $sumXX = array_sum(array_map(function ($x) {
            return $x * $x;
        }, range(1, $n)));

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        $forecast = [];
        for ($i = 1; $i <= $months; $i++) {
            $predicted = $slope * ($n + $i) + $intercept;
            $period = now()->addMonths($i)->format('Y-m');

            $forecast[] = [
                'period' => $period,
                'predicted_value' => max(0, $predicted),
            ];
        }

        return $forecast;
    }

    /**
     * Get subscription usage limits.
     */
    public function getSubscriptionLimits(Subscription $subscription): array
    {
        $plan = $subscription->plan;
        $features = $plan->features ?? [];

        $limits = [];

        foreach ($features as $feature) {
            if (isset($feature['limit'])) {
                $limits[$feature['name']] = [
                    'limit' => $feature['limit'],
                    'used' => $this->getUsedAmount($subscription->tenant_id, $feature['name']),
                    'remaining' => max(0, $feature['limit'] - $this->getUsedAmount($subscription->tenant_id, $feature['name'])),
                    'percentage' => min(100, ($this->getUsedAmount($subscription->tenant_id, $feature['name']) / max(1, $feature['limit'])) * 100),
                ];
            }
        }

        return $limits;
    }

    /**
     * Get used amount for a feature.
     */
    protected function getUsedAmount(int $tenantId, string $metricType): float
    {
        return UsageMetric::where('tenant_id', $tenantId)
            ->where('metric_type', $metricType)
            ->where('period', now()->format('Y-m'))
            ->sum('metric_value');
    }

    /**
     * Check if subscription is over limits.
     */
    public function isOverLimits(Subscription $subscription): array
    {
        $limits = $this->getSubscriptionLimits($subscription);
        $overLimits = [];

        foreach ($limits as $feature => $data) {
            if ($data['remaining'] <= 0) {
                $overLimits[] = [
                    'feature' => $feature,
                    'limit' => $data['limit'],
                    'used' => $data['used'],
                ];
            }
        }

        return $overLimits;
    }
}
