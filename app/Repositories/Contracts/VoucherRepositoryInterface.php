<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Plan;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface VoucherRepositoryInterface
{
    public function find(string|int $id): ?Voucher;

    public function findOrFail(string|int $id): Voucher;

    public function create(array $data): Voucher;

    public function update(Voucher $voucher, array $data): bool;

    public function delete(Voucher $voucher): bool;

    public function findByCode(string $code): ?Voucher;

    public function getActiveVouchers(?string $type = null, ?string $search = null, ?bool $isActive = null, int $perPage = 20): LengthAwarePaginator;

    public function getVouchersForPlan(int $planId): Collection;

    public function incrementUsage(int $voucherId): bool;

    public function canUseVoucher(Voucher $voucher, ?User $user = null, ?Plan $plan = null): bool;
}
