<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    public function find(string|int $id): ?Payment;

    public function findOrFail(string|int $id): Payment;

    public function create(array $data): Payment;

    public function update(Payment $payment, array $data): bool;

    public function delete(Payment $payment): bool;

    public function getByTenant(int $tenantId, ?string $status = null, ?string $paymentMethod = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Payment;

    public function getByInvoice(int $invoiceId): Collection;
}
