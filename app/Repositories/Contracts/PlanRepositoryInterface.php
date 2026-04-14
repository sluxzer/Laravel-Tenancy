<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PlanRepositoryInterface
{
    public function find(string|int $id): ?Plan;

    public function findOrFail(string|int $id): Plan;

    public function create(array $data): Plan;

    public function update(Plan $plan, array $data): bool;

    public function delete(Plan $plan): bool;

    public function getActivePlans(): Collection;

    public function getAllPlans(int $perPage = 20): LengthAwarePaginator;

    public function findBySlug(string $slug): ?Plan;

    public function getPopularPlans(): Collection;
}
