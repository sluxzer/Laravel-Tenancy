<?php

declare(strict_types=1);

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Export Data Request
 *
 * Validation request for triggering data export.
 */
class ExportDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|in:users,invoices,subscriptions,activities,analytics,custom',
            'format' => 'required|in:json,csv,excel',
            'filters' => 'nullable|array',
            'tables' => 'nullable|array',
            'description' => 'nullable|string',
        ];
    }
}
