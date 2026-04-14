<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Refund;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface RefundRepositoryInterface
{
    public function find(string|int $id): ?Refund;

    public function findOrFail(string|int $id): Refund;

    public function create(array $data): Refund;

    public function update(Refund $refund, array $data): bool;

    public function delete(Refund $refund): bool;

    public function getByTenant(int $tenantId, ?string $status = null, int $perPage = 20): LengthAwarePaginator;

    public function getByPayment(int $paymentId): Collection;
}
