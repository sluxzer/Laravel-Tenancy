<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Subscription;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * Cancel Subscription Request
 *
 * Validation request for canceling an active subscription.
 */
class CancelSubscriptionRequest extends FormRequest
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
            'reason' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.string' => 'The reason must be a string.',
            'reason.max' => 'The reason must not exceed 1000 characters.',
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
     * Get the cancellation reason from the validated request data.
     */
    public function getReason(): ?string
    {
        return $this->input('reason');
    }
}
