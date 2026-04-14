<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function find(string|int $id): ?User;

    public function findOrFail(string|int $id): User;

    public function create(array $data): User;

    public function update(User $user, array $data): bool;

    public function delete(User $user): bool;

    public function getByTenant(int $tenantId, int $perPage = 20): LengthAwarePaginator;

    public function findByEmail(string $email): ?User;

    public function findByTenantAndEmail(int $tenantId, string $email): ?User;
}
