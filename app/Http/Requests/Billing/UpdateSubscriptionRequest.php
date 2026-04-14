<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * Update Subscription Request
 *
 * Validation request for updating an existing subscription's plan or billing cycle.
 */
class UpdateSubscriptionRequest extends FormRequest
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
                'sometimes',
                'integer',
                Rule::exists(Plan::class, 'id')->where('is_active', true),
            ],
            'billing_cycle' => 'sometimes|in:monthly,yearly,quarterly',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_id.integer' => 'The plan ID must be a valid integer.',
            'plan_id.exists' => 'The selected plan does not exist or is not active.',
            'billing_cycle.in' => 'The billing cycle must be one of: monthly, yearly, or quarterly.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }

    /**
     * Get the subscription model from the route parameter.
     */
    public function getSubscription(): Subscription
    {
        return $this->route('subscription');
    }

    /**
     * Get the new plan model from the validated request data, or null if not provided.
     */
    public function getNewPlan(): ?Plan
    {
        $planId = $this->input('plan_id');

        return $planId ? Plan::findOrFail($planId) : null;
    }
}
