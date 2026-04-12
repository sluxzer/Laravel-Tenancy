<?php

declare(strict_types=1);

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Import Data Request
 *
 * Validation request for triggering data import.
 */
class ImportDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'type' => 'required|in:users,invoices,subscriptions,activities,analytics,custom',
            'mapping' => 'nullable|array',
            'skip_duplicates' => 'boolean',
            'description' => 'nullable|string',
        ];
    }
}
