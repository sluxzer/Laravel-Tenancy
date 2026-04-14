<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubscriptionServiceInterface
{
    public function getActiveSubscription(User $user): ?Subscription;

    public function getForTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator;

    public function create(User $user, Plan $plan, string $paymentMethod, ?string $paymentToken, string $billingCycle): Subscription;

    public function update(Subscription $subscription, array $data): Subscription;

    public function upgrade(Subscription $subscription, Plan $newPlan): Subscription;

    public function downgrade(Subscription $subscription, Plan $newPlan): Subscription;

    public function pause(Subscription $subscription): Subscription;

    public function resume(Subscription $subscription): Subscription;

    public function cancel(Subscription $subscription, ?string $reason = null): Subscription;

    public function renew(Subscription $subscription): Subscription;

    public function applyVoucher(Subscription $subscription, User $user, string $code): void;
}
