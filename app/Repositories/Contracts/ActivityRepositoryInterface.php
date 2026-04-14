<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ActivityRepositoryInterface
{
    public function find(string|int $id): ?Activity;

    public function findOrFail(string|int $id): Activity;

    public function create(array $data): Activity;

    public function delete(Activity $activity): bool;

    public function getByTenant(int $tenantId, ?string $type = null, ?int $userId = null, int $perPage = 20): LengthAwarePaginator;

    public function getByUser(int $userId, int $limit = 50): Collection;
}
