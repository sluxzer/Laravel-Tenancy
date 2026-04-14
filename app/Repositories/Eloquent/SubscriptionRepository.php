<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Plan;
use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    protected function model(): string
    {
        return Subscription::class;
    }

    public function find(string|int $id): ?Subscription
    {
        return $this->query()->find($id);
    }

    public function findOrFail(string|int $id): Subscription
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $data): Subscription
    {
        return $this->query()->create($data);
    }

    public function update(Subscription|Model $subscription, array $data): bool
    {
        return $subscription->update($data);
    }

    public function delete(Subscription|Model $subscription): bool
    {
        return $subscription->delete();
    }

    public function getActiveForUser(int $userId): ?Subscription
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->with('plan')
            ->first();
    }

    public function getActiveForTenant(int $tenantId): Collection
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with(['plan', 'user'])
            ->get();
    }

    public function getByTenant(
        int $tenantId,
        ?string $status = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $this->query()->where('tenant_id', $tenantId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query
            ->with(['plan', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findByTenant(int $tenantId, string|int $id): ?Subscription
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with('plan')
            ->first();
    }

    public function findForUpgrade(Subscription $subscription, Plan $newPlan): bool
    {
        return $this->query()
            ->where('id', $subscription->id)
            ->where('status', 'active')
            ->whereExists(fn ($query) => $query
                ->from('plans')
                ->whereColumn('plans.id', 'subscriptions.plan_id')
                ->where('price_monthly', '<', $newPlan->price_monthly)
            )
            ->exists();
    }

    public function countForTenant(int $tenantId, ?string $status = null): int
    {
        $query = $this->query()->where('tenant_id', $tenantId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->count();
    }
}
