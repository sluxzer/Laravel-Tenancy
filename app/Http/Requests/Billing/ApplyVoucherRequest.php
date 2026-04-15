<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Subscription;
use App\Models\Voucher;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * Apply Voucher Request
 *
 * Validation request for applying a voucher to a subscription.
 */
class ApplyVoucherRequest extends FormRequest
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
            'voucher_id' => [
                'required',
                'integer',
                Rule::exists(Voucher::class, 'id'),
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'voucher_id.required' => 'The voucher ID is required.',
            'voucher_id.integer' => 'The voucher ID must be a valid integer.',
            'voucher_id.exists' => 'The selected voucher does not exist.',
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
        return Subscription::findOrFail($this->route('subscription'));
    }

    /**
     * Get the voucher model from the validated request data.
     */
    public function getVoucher(): Voucher
    {
        $voucherId = $this->input('voucher_id');

        return Voucher::findOrFail($voucherId);
    }
}
