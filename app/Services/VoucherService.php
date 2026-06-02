<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
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
    public function create(array $data): Voucher
    {
        return Voucher::create($data);
    }

    /**
     * Update a voucher.
     */
    public function update(Voucher $voucher, array $data): Voucher
    {
        $voucher->update($data);

        return $voucher;
    }

    /**
     * Delete a voucher.
     */
    public function delete(Voucher $voucher): void
    {
        $voucher->delete();
    }

    /**
     * Validate a voucher code.
     */
    public function validate(string $code, User $user): array
    {
        $voucher = Voucher::where('code', $code)->first();

        if (! $voucher) {
            return ['valid' => false, 'message' => 'Voucher not found.'];
        }

        if (! $voucher->is_active) {
            return ['valid' => false, 'message' => 'Voucher is not active.'];
        }

        if ($voucher->max_uses && $voucher->used_count >= $voucher->max_uses) {
            return ['valid' => false, 'message' => 'Voucher has reached maximum uses.'];
        }

        if ($voucher->valid_from && Carbon::parse($voucher->valid_from)->isFuture()) {
            return ['valid' => false, 'message' => 'Voucher is not yet valid.'];
        }

        if ($voucher->valid_until && Carbon::parse($voucher->valid_until)->isPast()) {
            return ['valid' => false, 'message' => 'Voucher has expired.'];
        }

        return ['valid' => true, 'message' => 'Voucher is valid.', 'voucher' => $voucher];
    }

    /**
     * Apply voucher to subscription amount.
     */
    public function apply(string $code, User $user, float $amount): array
    {
        $validation = $this->validate($code, $user);

        if (! $validation['valid']) {
            return ['valid' => false, 'message' => $validation['message']];
        }

        $voucher = $validation['voucher'];
        $discountAmount = 0;

        if ($voucher->type === 'percentage') {
            $discountAmount = $amount * ($voucher->value / 100);
            return [
                'valid' => true,
                'discount_amount' => $discountAmount,
                'final_amount' => $amount - $discountAmount,
            ];
        }

        if ($voucher->type === 'fixed') {
            $discountAmount = (float) $voucher->value;
            return [
                'valid' => true,
                'discount_amount' => $discountAmount,
                'final_amount' => $amount - $discountAmount,
            ];
        }

        if ($voucher->type === 'free_trial') {
            return [
                'valid' => true,
                'trial_days' => (int) $voucher->value,
            ];
        }

        return ['valid' => false, 'message' => 'Invalid voucher type.'];
    }
}
