<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface
{
    public function find(string|int $id): ?Invoice;

    public function findOrFail(string|int $id): Invoice;

    public function create(array $data): Invoice;

    public function update(Invoice $invoice, array $data): bool;

    public function delete(Invoice $invoice): bool;

    public function getByTenant(int $tenantId, ?string $status = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Invoice;

    public function getBySubscription(int $subscriptionId): Collection;

    public function getOverdueInvoices(int $tenantId): Collection;
}
