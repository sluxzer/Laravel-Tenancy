<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Subscription Request
 *
 * Validation request for subscription updates.
 */
class UpdateSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plan_id' => 'required|integer|exists:plans,id',
            'billing_cycle' => 'sometimes|in:monthly,yearly,quarterly',
        ];
    }
}
