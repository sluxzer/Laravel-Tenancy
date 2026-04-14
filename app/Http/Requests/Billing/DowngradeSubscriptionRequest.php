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
 * Downgrade Subscription Request
 *
 * Validation request for downgrading a subscription to a lower-tier plan.
 */
class DowngradeSubscriptionRequest extends FormRequest
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
     * Get the new plan model from the validated request data.
     */
    public function getNewPlan(): Plan
    {
        $planId = $this->input('plan_id');

        return Plan::findOrFail($planId);
    }
}
