<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FeatureFlagRepositoryInterface
{
    public function find(string|int $id): ?FeatureFlag;

    public function findOrFail(string|int $id): FeatureFlag;

    public function create(array $data): FeatureFlag;

    public function update(FeatureFlag $flag, array $data): bool;

    public function delete(FeatureFlag $flag): bool;

    public function getByTenant(int $tenantId, ?bool $isEnabled = null, int $perPage = 20): LengthAwarePaginator;

    public function findByKey(int $tenantId, string $key): ?FeatureFlag;

    public function getEnabledFlags(int $tenantId): Collection;
}
