<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Invitation;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvitationRepositoryInterface
{
    public function find(string|int $id): ?Invitation;

    public function findOrFail(string|int $id): Invitation;

    public function create(array $data): Invitation;

    public function update(Invitation $invitation, array $data): bool;

    public function delete(Invitation $invitation): bool;

    public function getByToken(string $token): ?Invitation;

    public function getByTenant(int $tenantId, ?string $status = null, int $perPage = 20): LengthAwarePaginator;

    public function getByEmail(string $email): ?Invitation;
}
