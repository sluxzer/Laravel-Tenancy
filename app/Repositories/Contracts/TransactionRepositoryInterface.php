<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function find(string|int $id): ?Transaction;

    public function findOrFail(string|int $id): Transaction;

    public function create(array $data): Transaction;

    public function update(Transaction $transaction, array $data): bool;

    public function delete(Transaction $transaction): bool;

    public function getByTenant(int $tenantId, ?string $type = null, int $perPage = 20): LengthAwarePaginator;
}
