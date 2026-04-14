<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\GdprDeletionRequest;

interface GdprDeletionRequestRepositoryInterface
{
    public function find(string|int $id): ?GdprDeletionRequest;

    public function findOrFail(string|int $id): GdprDeletionRequest;

    public function create(array $data): GdprDeletionRequest;

    public function update(GdprDeletionRequest $request, array $data): bool;

    public function delete(GdprDeletionRequest $request): bool;

    public function findByUser(int $userId): ?GdprDeletionRequest;

    public function findByToken(string $token): ?GdprDeletionRequest;
}
