<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WebhookRepositoryInterface
{
    public function find(string|int $id): ?Webhook;

    public function findOrFail(string|int $id): Webhook;

    public function create(array $data): Webhook;

    public function update(Webhook $webhook, array $data): bool;

    public function delete(Webhook $webhook): bool;

    public function getByTenant(int $tenantId, ?bool $isActive = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Webhook;

    public function getByEvent(int $tenantId, string $event): Collection;
}
