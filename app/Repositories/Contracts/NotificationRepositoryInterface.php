<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    public function find(string|int $id): ?Notification;

    public function findOrFail(string|int $id): Notification;

    public function create(array $data): Notification;

    public function update(Notification $notification, array $data): bool;

    public function delete(Notification $notification): bool;

    public function getByTenant(int $tenantId, ?string $status = null, ?string $type = null, ?int $userId = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Notification;

    public function markAsRead(int $notificationId): bool;

    public function markAllAsReadForUser(int $userId): int;

    public function getUnreadCountForUser(int $userId): int;

    public function getByUser(int $userId, int $limit = 20): Collection;
}
