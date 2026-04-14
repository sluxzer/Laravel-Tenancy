<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\CustomReport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomReportRepositoryInterface
{
    public function find(string|int $id): ?CustomReport;

    public function findOrFail(string|int $id): CustomReport;

    public function create(array $data): CustomReport;

    public function update(CustomReport $report, array $data): bool;

    public function delete(CustomReport $report): bool;

    public function getByTenant(int $tenantId, ?bool $isActive = null, ?string $type = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?CustomReport;

    public function getActiveReports(int $tenantId): Collection;
}
