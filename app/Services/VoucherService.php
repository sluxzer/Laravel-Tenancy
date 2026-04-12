<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;

/**
 * Voucher Service
 *
 * Handles voucher/discount code management.
 */
class VoucherService
{
    /**
     * Create a voucher.
     */
    public function createVoucher(Tenant $tenant, array $data): Voucher
    {
        return Voucher::create([
            'tenant_id' => $tenant->id,
            'code' => $this->generateUniqueCode($tenant),
            'name' => $data['name'],
            'description' => $data['description'],
            'type' => $data['type'] ?? 'percentage',
            'value' => $data['value'],
            'plan_id' => $data['plan_id'] ?? null,
            'max_uses' => $data['max_uses'] ?? 1,
            'used_count' => 0,
            'expires_at' => $data['expires_at'] ? Carbon::parse($data['expires_at'])->toDateTimeString() : null,
            'is_active' => true,
        ]);
    }

    /**
     * Validate a voucher code.
     */
    public function validateVoucher(string $code, Tenant $tenant): array
    {
        $voucher = Voucher::where('tenant_id', $tenant->id)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $voucher) {
            return ['valid' => false, 'message' => 'Voucher not found.'];
        }

        if (! $voucher->expires_at || Carbon::parse($voucher->expires_at)->isPast()) {
            return ['valid' => false, 'message' => 'Voucher has expired.'];
        }

        if ($voucher->max_uses && $voucher->used_count >= $voucher->max_uses) {
            return ['valid' => false, 'message' => 'Voucher has reached maximum usage.'];
        }

        return ['valid' => true, 'message' => 'Voucher is valid.', 'voucher' => $voucher];
    }

    /**
     * Apply voucher to subscription.
     */
    public function applyVoucher(Subscription $subscription, Voucher $voucher): Subscription
    {
        $subscription->update([
            'metadata' => array_merge($subscription->metadata ?? [], ['applied_voucher_id' => $voucher->id]),
        ]);

        return $subscription->fresh();
    }

    /**
     * Increment voucher usage.
     */
    public function incrementUsage(Voucher $voucher): Voucher
    {
        $voucher->increment('used_count');

        return $voucher->fresh();
    }

    /**
     * Get active vouchers for tenant.
     */
    public function getActiveVouchers(Tenant $tenant): Collection
    {
        return Voucher::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
