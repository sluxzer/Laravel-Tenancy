<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class PlanRepository extends BaseRepository implements PlanRepositoryInterface
{
    protected function model(): string
    {
        return Plan::class;
    }

    public function find(string|int $id): ?Plan
    {
        return parent::find($id);
    }

    public function findOrFail(string|int $id): Plan
    {
        return parent::findOrFail($id);
    }

    public function create(array $data): Plan
    {
        return parent::create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return parent::update($model, $data);
    }

    public function delete(Model $model): bool
    {
        return parent::delete($model);
    }

    public function getActivePlans(): Collection
    {
        return $this->query()->where('is_active', true)->get();
    }

    public function getAllPlans(int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Plan
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function getPopularPlans(): Collection
    {
        return $this->query()->where('is_popular', true)->where('is_active', true)->get();
    }
}
