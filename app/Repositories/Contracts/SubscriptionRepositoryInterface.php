<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubscriptionRepositoryInterface
{
    public function find(string|int $id): ?Subscription;

    public function findOrFail(string|int $id): Subscription;

    public function create(array $data): Subscription;

    public function update(Model $subscription, array $data): bool;

    public function delete(Model $subscription): bool;

    public function getActiveForUser(int $userId): ?Subscription;

    public function getActiveForTenant(int $tenantId): Collection;

    public function getByTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Subscription;

    public function findForUpgrade(Subscription $subscription, Plan $newPlan): bool;

    public function countForTenant(int $tenantId, ?string $status = null): int;
}
