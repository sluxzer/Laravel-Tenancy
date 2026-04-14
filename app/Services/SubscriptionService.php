<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SubscriptionException;
use App\Exceptions\VoucherException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Voucher;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\VoucherRepositoryInterface;
use App\Services\Contracts\SubscriptionServiceInterface;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Subscription Service
 *
 * Handles subscription lifecycle, plan changes, and billing logic.
 * Uses repositories for data access and domain exceptions for error handling.
 */
class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private PlanRepositoryInterface $planRepository,
        private VoucherRepositoryInterface $voucherRepository
    ) {}

    /**
     * Get the active subscription for a tenant.
     */
    public function getActiveSubscription(Tenant $tenant): ?Subscription
    {
        $subscriptions = $this->subscriptionRepository->getActiveForTenant($tenant->id);

        return $subscriptions->first();
    }

    /**
     * Get subscriptions for a tenant with optional filtering.
     */
    public function getForTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getByTenant($tenantId, $status, $perPage);
    }

    /**
     * Create a new subscription.
     */
    public function create(Tenant $tenant, Plan $plan, string $billingCycle, ?int $userId = null, array $metadata = []): Subscription
    {
        // Check if tenant already has an active subscription
        $existingSubscription = $this->getActiveSubscription($tenant);
        if ($existingSubscription !== null && $this->isActive($existingSubscription)) {
            throw SubscriptionException::alreadyActive();
        }

        // Validate plan is active
        if (! $plan->is_active) {
            throw SubscriptionException::cannotCancel('Selected plan is not available');
        }

        // Calculate dates based on billing cycle
        $startDate = now();
        $endDate = null;

        if ($billingCycle === 'monthly') {
            $endDate = $startDate->copy()->addMonth();
        } elseif ($billingCycle === 'yearly') {
            $endDate = $startDate->copy()->addYear();
        }

        return DB::transaction(function () use ($tenant, $plan, $userId, $metadata, $startDate, $endDate) {
            $data = [
                'tenant_id' => $tenant->id,
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'metadata' => array_merge($metadata, [
                    'billing_cycle' => 'monthly',
                    'original_plan_id' => $plan->id,
                ]),
            ];

            return $this->subscriptionRepository->create($data);
        });
    }

    /**
     * Update subscription details.
     */
    public function update(Subscription $subscription, array $data): Subscription
    {
        $this->subscriptionRepository->update($subscription, $data);

        // Update the model instance with new data
        foreach ($data as $key => $value) {
            $subscription->{$key} = $value;
        }

        return $subscription;
    }

    /**
     * Upgrade a subscription to a higher-tier plan.
     */
    public function upgrade(Subscription $subscription, Plan $newPlan): Subscription
    {
        // Check if subscription is in a valid state for upgrade
        $this->validateSubscriptionForModification($subscription, ['active', 'trial']);

        // Validate new plan is active
        if (! $newPlan->is_active) {
            throw SubscriptionException::cannotCancel('New plan is not available');
        }

        // Get current plan for comparison
        $currentPlan = $subscription->plan;
        if ($currentPlan === null) {
            throw SubscriptionException::notFound();
        }

        // Validate upgrade (new plan should be more expensive or higher tier)
        if ($newPlan->price_monthly <= $currentPlan->price_monthly) {
            throw SubscriptionException::cannotCancel('Cannot upgrade to a lower-priced plan. Use downgrade instead.');
        }

        return DB::transaction(function () use ($subscription, $newPlan, $currentPlan) {
            $metadata = $subscription->metadata ?? [];
            $metadata['previous_plan_id'] = $currentPlan->id;
            $metadata['upgrade_date'] = now()->toIso8601String();

            $this->subscriptionRepository->update($subscription, [
                'plan_id' => $newPlan->id,
                'metadata' => $metadata,
            ]);

            // Update the model instance
            $subscription->plan_id = $newPlan->id;
            $subscription->metadata = $metadata;

            return $subscription;
        });
    }

    /**
     * Downgrade a subscription to a lower-tier plan.
     */
    public function downgrade(Subscription $subscription, Plan $newPlan): Subscription
    {
        // Check if subscription is in a valid state for downgrade
        $this->validateSubscriptionForModification($subscription, ['active', 'trial']);

        // Validate new plan is active
        if (! $newPlan->is_active) {
            throw SubscriptionException::cannotCancel('New plan is not available');
        }

        // Get current plan for comparison
        $currentPlan = $subscription->plan;
        if ($currentPlan === null) {
            throw SubscriptionException::notFound();
        }

        // Validate downgrade (new plan should be cheaper or lower tier)
        if ($newPlan->price_monthly >= $currentPlan->price_monthly) {
            throw SubscriptionException::cannotCancel('Cannot downgrade to a higher-priced plan. Use upgrade instead.');
        }

        return DB::transaction(function () use ($subscription, $currentPlan) {
            $metadata = $subscription->metadata ?? [];
            $metadata['previous_plan_id'] = $currentPlan->id;
            $metadata['downgrade_date'] = now()->toIso8601String();
            $metadata['downgrade_effective_at'] = $subscription->ends_at?->toIso8601String();

            // Downgrade takes effect at next billing cycle
            $this->subscriptionRepository->update($subscription, [
                'metadata' => $metadata,
            ]);

            // Update the model instance
            $subscription->metadata = $metadata;

            return $subscription;
        });
    }

    /**
     * Pause an active subscription.
     */
    public function pause(Subscription $subscription): Subscription
    {
        if (! $this->isActive($subscription)) {
            throw SubscriptionException::cannotPause();
        }

        $this->subscriptionRepository->update($subscription, [
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        // Update the model instance
        $subscription->status = 'paused';
        $subscription->paused_at = now();

        return $subscription;
    }

    /**
     * Resume a paused subscription.
     */
    public function resume(Subscription $subscription): Subscription
    {
        if ($subscription->status !== 'paused') {
            throw SubscriptionException::cannotResume();
        }

        $this->subscriptionRepository->update($subscription, [
            'status' => 'active',
            'paused_at' => null,
        ]);

        // Update the model instance
        $subscription->status = 'active';
        $subscription->paused_at = null;

        return $subscription;
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Subscription $subscription, ?string $reason = null): Subscription
    {
        // Check if subscription can be cancelled
        if (! in_array($subscription->status, ['active', 'trial', 'paused'])) {
            throw SubscriptionException::cannotCancel("Subscription is {$subscription->status} and cannot be cancelled");
        }

        $metadata = $subscription->metadata ?? [];
        $metadata['cancellation_reason'] = $reason;
        $metadata['cancelled_by'] = auth()->id() ?? null;

        $this->subscriptionRepository->update($subscription, [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'metadata' => $metadata,
        ]);

        // Update the model instance
        $subscription->status = 'cancelled';
        $subscription->cancelled_at = now();
        $subscription->metadata = $metadata;

        return $subscription;
    }

    /**
     * Renew an expired or expiring subscription.
     */
    public function renew(Subscription $subscription): Subscription
    {
        // Check if subscription can be renewed
        if (! in_array($subscription->status, ['active', 'expired', 'cancelled'])) {
            throw SubscriptionException::cannotRenew();
        }

        // If already active and not expiring soon, don't renew
        if ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->gt(now()->addDays(7))) {
            throw SubscriptionException::cannotRenew('Subscription is not due for renewal');
        }

        // Calculate new end date
        $currentEndDate = $subscription->ends_at ?? now();
        $newEndDate = $currentEndDate->copy()->addMonth();

        $this->subscriptionRepository->update($subscription, [
            'status' => 'active',
            'ends_at' => $newEndDate,
            'cancelled_at' => null, // Clear cancellation if any
        ]);

        // Update the model instance
        $subscription->status = 'active';
        $subscription->ends_at = $newEndDate;
        $subscription->cancelled_at = null;

        return $subscription;
    }

    /**
     * Apply a voucher to a subscription.
     */
    public function applyVoucher(Subscription $subscription, Voucher $voucher): void
    {
        // Validate voucher is active and usable
        if (! $this->voucherRepository->canUseVoucher($voucher, $subscription->user, $subscription->plan)) {
            throw VoucherException::notActive();
        }

        // Validate voucher hasn't expired
        if ($voucher->expires_at && Carbon::parse($voucher->expires_at)->isPast()) {
            throw VoucherException::expired();
        }

        // Check if max uses reached
        if ($voucher->max_uses && $voucher->uses_count >= $voucher->max_uses) {
            throw VoucherException::maxUsesReached();
        }

        // Validate voucher is valid for the subscription's plan
        if ($voucher->plan_id && $voucher->plan_id !== $subscription->plan_id) {
            throw VoucherException::invalidForPlan();
        }

        // Check if voucher already applied to this subscription
        $metadata = $subscription->metadata ?? [];
        if (isset($metadata['applied_vouchers']) && in_array($voucher->id, $metadata['applied_vouchers'])) {
            throw VoucherException::alreadyUsed();
        }

        DB::transaction(function () use ($subscription, $voucher, $metadata) {
            // Update subscription metadata with voucher
            $appliedVouchers = $metadata['applied_vouchers'] ?? [];
            $appliedVouchers[] = $voucher->id;

            $voucherData = [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'discount_type' => $voucher->discount_type,
                'discount_value' => $voucher->discount_value,
                'applied_at' => now()->toIso8601String(),
            ];

            $metadata['applied_vouchers'] = $appliedVouchers;
            $metadata['current_voucher'] = $voucherData;

            $this->subscriptionRepository->update($subscription, ['metadata' => $metadata]);

            // Increment voucher usage
            $this->voucherRepository->incrementUsage($voucher->id);
        });
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(Subscription $subscription): bool
    {
        return $subscription->status === 'active' &&
            ($subscription->ends_at === null || $subscription->ends_at->isFuture());
    }

    /**
     * Check if subscription is within grace period.
     */
    public function isInGracePeriod(Subscription $subscription): bool
    {
        return $subscription->grace_period_ends_at !== null &&
            $subscription->grace_period_ends_at->isFuture();
    }

    /**
     * Validate subscription is in a valid state for modification.
     *
     * @throws SubscriptionException
     */
    private function validateSubscriptionForModification(Subscription $subscription, array $validStatuses): void
    {
        if (! in_array($subscription->status, $validStatuses, true)) {
            throw SubscriptionException::invalidStatus($subscription->status, $validStatuses);
        }
    }
}
