<?php

declare(strict_types=1);

namespace App\Http\Controllers\Usage;

use App\Http\Controllers\Controller;
use App\Models\UsageMetric;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Usage Metric Controller (Tenant)
 *
 * Tenant-level usage metric management.
 */
class UsageMetricController extends Controller
{
    /**
     * Get all usage metrics for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = UsageMetric::where('tenant_id', $tenant->id);

        if ($request->has('metric_type')) {
            $query->where('metric_type', $request->input('metric_type'));
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }

        $metrics = $query->orderBy('date', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $metrics->items(),
            'pagination' => [
                'total' => $metrics->total(),
                'per_page' => $metrics->perPage(),
                'current_page' => $metrics->currentPage(),
                'last_page' => $metrics->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific usage metric.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $metric = UsageMetric::where('tenant_id', $tenant->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $metric,
        ]);
    }

    /**
     * Record a usage metric.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'metric_type' => 'required|string|max:255',
            'value' => 'required|numeric',
            'date' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $metric = UsageMetric::create([
            'tenant_id' => $tenant->id,
            'metric_type' => $validated['metric_type'],
            'value' => $validated['value'],
            'date' => $validated['date'] ?? now()->toDateString(),
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usage metric recorded successfully',
            'data' => $metric,
        ], 201);
    }

    /**
     * Get usage summary.
     */
    public function summary(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();

        $summary = UsageMetric::where('tenant_id', $tenant->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('
                metric_type,
                SUM(value) as total_value,
                COUNT(*) as record_count,
                MIN(date) as first_record,
                MAX(date) as last_record
            ')
            ->groupBy('metric_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Get usage by metric type.
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();

        $metrics = UsageMetric::where('tenant_id', $tenant->id)
            ->where('metric_type', $type)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Record bulk metrics.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'metrics' => 'required|array',
            'metrics.*.metric_type' => 'required|string|max:255',
            'metrics.*.value' => 'required|numeric',
            'metrics.*.date' => 'nullable|date',
            'metrics.*.metadata' => 'nullable|array',
        ]);

        $metrics = collect($validated['metrics'])->map(function ($metric) use ($tenant) {
            return [
                'tenant_id' => $tenant->id,
                'metric_type' => $metric['metric_type'],
                'value' => $metric['value'],
                'date' => $metric['date'] ?? now()->toDateString(),
                'metadata' => $metric['metadata'] ?? [],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        UsageMetric::insert($metrics);

        return response()->json([
            'success' => true,
            'message' => count($metrics).' usage metrics recorded successfully',
        ], 201);
    }
}
