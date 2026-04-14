<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Subscription Request
 *
 * Validation request for creating a new subscription.
 * This request validates the data needed to create a subscription
 * without handling payment details (payment is handled separately).
 */
class CreateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists(Plan::class, 'id')->where('is_active', true),
            ],
            'billing_cycle' => [
                'required',
                'string',
                'in:monthly,yearly,quarterly',
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id'),
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'The plan ID is required.',
            'plan_id.integer' => 'The plan ID must be a valid integer.',
            'plan_id.exists' => 'The selected plan does not exist or is not active.',
            'billing_cycle.required' => 'The billing cycle is required.',
            'billing_cycle.string' => 'The billing cycle must be a string.',
            'billing_cycle.in' => 'The billing cycle must be one of: monthly, yearly, or quarterly.',
            'user_id.integer' => 'The user ID must be a valid integer.',
            'user_id.exists' => 'The specified user does not exist.',
            'metadata.array' => 'The metadata must be an array.',
        ];
    }

    /**
     * Get the plan model from the validated request data.
     */
    public function getPlan(): Plan
    {
        $planId = $this->input('plan_id');

        return Plan::findOrFail($planId);
    }

    /**
     * Get the subscription user model from the validated request data, or null if not provided.
     */
    public function getSubscriptionUser(): ?User
    {
        $userId = $this->input('user_id');

        return $userId ? User::find($userId) : null;
    }

    /**
     * Get the metadata from the validated request data, or empty array if not provided.
     */
    public function getMetadata(): array
    {
        return $this->input('metadata', []);
    }
}
