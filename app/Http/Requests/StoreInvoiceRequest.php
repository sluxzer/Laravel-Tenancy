<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Invoice Request
 *
 * Validation request for creating a new invoice.
 */
class StoreInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subscription_id' => 'required|integer|exists:subscriptions,id',
            'due_date' => 'required|date',
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|max:3',
            'notes' => 'nullable|string',
        ];
    }
}
