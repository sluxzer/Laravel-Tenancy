<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Tenant;

interface TenantRepositoryInterface
{
    public function find(string|int $id): ?Tenant;

    public function findOrFail(string|int $id): Tenant;

    public function create(array $data): Tenant;

    public function update(Tenant $tenant, array $data): bool;

    public function delete(Tenant $tenant): bool;

    public function findByDomain(string $domain): ?Tenant;
}
