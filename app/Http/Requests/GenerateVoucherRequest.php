<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Generate Voucher Request
 *
 * Validation request for generating bulk vouchers.
 */
class GenerateVoucherRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prefix' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed,free_trial',
            'value' => 'required_if:type==percentage||type==fixed|numeric|min:0',
            'trial_days' => 'required_if:type==free_trial|integer|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date',
            'plan_id' => 'nullable|exists:plans,id',
            'count' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
        ];
    }
}
