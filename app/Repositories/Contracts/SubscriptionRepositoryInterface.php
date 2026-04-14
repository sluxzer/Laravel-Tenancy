<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubscriptionRepositoryInterface
{
    public function find(string|int $id): ?Subscription;

    public function findOrFail(string|int $id): Subscription;

    public function create(array $data): Subscription;

    public function update(Subscription $subscription, array $data): bool;

    public function delete(Subscription $subscription): bool;

    public function getActiveForUser(int $userId): ?Subscription;

    public function getActiveForTenant(int $tenantId): Collection;

    public function getByTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Subscription;

    public function findForUpgrade(Subscription $subscription, Plan $newPlan): bool;

    public function countForTenant(int $tenantId, ?string $status = null): int;
}
