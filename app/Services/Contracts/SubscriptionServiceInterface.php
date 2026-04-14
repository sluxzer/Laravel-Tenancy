<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Voucher;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Subscription Service Interface
 *
 * Defines the contract for subscription lifecycle management including creation,
 * modification, cancellation, and billing operations. All methods must maintain
 * data consistency and properly handle tenant-based subscription relationships.
 */
interface SubscriptionServiceInterface
{
    /**
     * Get the active subscription for a tenant.
     *
     * @param  Tenant  $tenant  The tenant entity
     * @return Subscription|null The active subscription or null if not found
     */
    public function getActiveSubscription(Tenant $tenant): ?Subscription;

    /**
     * Get subscriptions for a tenant with optional filtering.
     *
     * @param  int  $tenantId  The tenant ID
     * @param  string|null  $status  Optional status filter (active, paused, cancelled, etc.)
     * @param  int  $perPage  Number of items per page
     * @return LengthAwarePaginator Paginated subscription list
     */
    public function getForTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator;

    /**
     * Create a new subscription.
     *
     * @param  Tenant  $tenant  The tenant to create the subscription for
     * @param  Plan  $plan  The plan to subscribe to
     * @param  string  $billingCycle  The billing cycle (monthly, yearly, etc.)
     * @param  int|null  $userId  Optional user ID associated with the subscription
     * @param  array  $metadata  Additional metadata for the subscription
     * @return Subscription The created subscription
     */
    public function create(Tenant $tenant, Plan $plan, string $billingCycle, ?int $userId = null, array $metadata = []): Subscription;

    /**
     * Update subscription details.
     *
     * @param  Subscription  $subscription  The subscription to update
     * @param  array  $data  The data to update
     * @return Subscription The updated subscription
     */
    public function update(Subscription $subscription, array $data): Subscription;

    /**
     * Upgrade a subscription to a higher-tier plan.
     *
     * @param  Subscription  $subscription  The subscription to upgrade
     * @param  Plan  $newPlan  The new plan to upgrade to
     * @return Subscription The upgraded subscription
     */
    public function upgrade(Subscription $subscription, Plan $newPlan): Subscription;

    /**
     * Downgrade a subscription to a lower-tier plan.
     *
     * @param  Subscription  $subscription  The subscription to downgrade
     * @param  Plan  $newPlan  The new plan to downgrade to
     * @return Subscription The downgraded subscription
     */
    public function downgrade(Subscription $subscription, Plan $newPlan): Subscription;

    /**
     * Pause an active subscription.
     *
     * @param  Subscription  $subscription  The subscription to pause
     * @return Subscription The paused subscription
     */
    public function pause(Subscription $subscription): Subscription;

    /**
     * Resume a paused subscription.
     *
     * @param  Subscription  $subscription  The subscription to resume
     * @return Subscription The resumed subscription
     */
    public function resume(Subscription $subscription): Subscription;

    /**
     * Cancel a subscription.
     *
     * @param  Subscription  $subscription  The subscription to cancel
     * @param  string|null  $reason  Optional cancellation reason
     * @return Subscription The cancelled subscription
     */
    public function cancel(Subscription $subscription, ?string $reason = null): Subscription;

    /**
     * Renew an expired or expiring subscription.
     *
     * @param  Subscription  $subscription  The subscription to renew
     * @return Subscription The renewed subscription
     */
    public function renew(Subscription $subscription): Subscription;

    /**
     * Apply a voucher to a subscription.
     *
     * @param  Subscription  $subscription  The subscription to apply the voucher to
     * @param  Voucher  $voucher  The validated voucher object to apply
     */
    public function applyVoucher(Subscription $subscription, Voucher $voucher): void;
}
