<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ExportJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExportJobRepositoryInterface
{
    public function find(string|int $id): ?ExportJob;

    public function findOrFail(string|int $id): ExportJob;

    public function create(array $data): ExportJob;

    public function update(ExportJob $job, array $data): bool;

    public function delete(ExportJob $job): bool;

    public function getByTenant(int $tenantId, ?string $status = null, ?string $type = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?ExportJob;
}
