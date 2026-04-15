<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Plan;
use App\Models\User;
use App\Models\Voucher;
use App\Repositories\Contracts\VoucherRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class VoucherRepository extends BaseRepository implements VoucherRepositoryInterface
{
    protected function model(): string
    {
        return Voucher::class;
    }

    public function find(string|int $id): ?Voucher
    {
        return $this->query()->find($id);
    }

    public function findOrFail(string|int $id): Voucher
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $data): Voucher
    {
        return $this->query()->create($data);
    }

    public function update(Voucher|Model $voucher, array $data): bool
    {
        return $voucher->update($data);
    }

    public function delete(Voucher|Model $voucher): bool
    {
        return $voucher->delete();
    }

    public function findByCode(string $code): ?Voucher
    {
        return $this->query()->where('code', $code)->first();
    }

    public function getActiveVouchers(
        ?string $type = null,
        ?string $search = null,
        ?bool $isActive = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $this->query();

        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($search !== null) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getVouchersForPlan(int $planId): Collection
    {
        return $this->query()
            ->where('plan_id', $planId)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->get();
    }

    public function incrementUsage(int $voucherId): bool
    {
        return $this->query()->where('id', $voucherId)->increment('used_count') > 0;
    }

    public function canUseVoucher(Voucher $voucher, ?User $user = null, ?Plan $plan = null): bool
    {
        // Check if voucher is active
        if (! $voucher->is_active) {
            return false;
        }

        // Check if voucher has expired
        if ($voucher->expires_at && $voucher->expires_at->isPast()) {
            return false;
        }

        // Check max uses
        if ($voucher->max_uses !== null && $voucher->used_count >= $voucher->max_uses) {
            return false;
        }

        // Check plan restriction
        if ($voucher->plan_id !== null && $plan !== null && $voucher->plan_id !== $plan->id) {
            return false;
        }

        // Additional checks could be added here (user-specific limits, etc.)

        return true;
    }
}
