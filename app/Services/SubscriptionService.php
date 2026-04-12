<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Subscription Service
 *
 * Handles subscription lifecycle, plan changes, and billing logic.
 */
class SubscriptionService
{
    /**
     * Create a new subscription.
     */
    public function createSubscription(Tenant $tenant, array $data): Subscription
    {
        return DB::transaction(function () use ($tenant, $data) {
            return Subscription::create([
                'tenant_id' => $tenant->id,
                'user_id' => $data['user_id'] ?? null,
                'plan_id' => $data['plan_id'],
                'status' => 'active',
                'starts_at' => Carbon::parse($data['starts_at'] ?? 'now')->toDateTimeString(),
                'ends_at' => $data['ends_at'] ? Carbon::parse($data['ends_at'])->toDateTimeString() : null,
                'trial_ends_at' => $data['trial_ends_at'] ? Carbon::parse($data['trial_ends_at'])->toDateTimeString() : null,
                'grace_period_ends_at' => $data['grace_period_ends_at'] ? Carbon::parse($data['grace_period_ends_at'])->toDateTimeString() : null,
                'metadata' => $data['metadata'] ?? [],
            ]);
        });
    }

    /**
     * Update subscription status.
     */
    public function updateStatus(Subscription $subscription, string $status): Subscription
    {
        $subscription->update([
            'status' => $status,
            'cancelled_at' => in_array($status, ['cancelled', 'expired']) ? now() : null,
        ]);

        return $subscription->fresh();
    }

    /**
     * Upgrade subscription to new plan.
     */
    public function upgradePlan(Subscription $subscription, int $newPlanId): Subscription
    {
        $subscription->update([
            'plan_id' => $newPlanId,
            'metadata->previous_plan_id' => $subscription->plan_id,
        ]);

        return $subscription->fresh();
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(Subscription $subscription, ?string $reason = null): Subscription
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'metadata->cancellation_reason' => $reason,
        ]);

        return $subscription->fresh();
    }

    /**
     * Pause subscription.
     */
    public function pauseSubscription(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        return $subscription->fresh();
    }

    /**
     * Resume subscription.
     */
    public function resumeSubscription(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'active',
            'paused_at' => null,
        ]);

        return $subscription->fresh();
    }

    /**
     * Renew subscription.
     */
    public function renewSubscription(Subscription $subscription, int $durationMonths = 1): Subscription
    {
        $startDate = Carbon::parse($subscription->starts_at);
        $newEndDate = $startDate->addMonths($durationMonths);

        $subscription->update([
            'ends_at' => $newEndDate->toDateTimeString(),
            'status' => 'active',
        ]);

        return $subscription->fresh();
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(Subscription $subscription): bool
    {
        return $subscription->status === 'active' &&
               ($subscription->ends_at === null || Carbon::parse($subscription->ends_at)->isFuture());
    }

    /**
     * Check if subscription is within grace period.
     */
    public function isInGracePeriod(Subscription $subscription): bool
    {
        return $subscription->grace_period_ends_at !== null &&
               Carbon::parse($subscription->grace_period_ends_at)->isFuture();
    }

    /**
     * Get subscription for tenant.
     */
    public function getTenantSubscription(Tenant $tenant): ?Subscription
    {
        return Subscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'paused', 'trial'])
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
