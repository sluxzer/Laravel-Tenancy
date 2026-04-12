<?php

declare(strict_types=1);

namespace App\Http\Requests\Usage;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Usage Metric Request
 *
 * Validation request for creating a usage metric entry.
 */
class CreateUsageMetricRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'metric_type' => 'required|string|max:255',
            'value' => 'required|numeric',
            'date' => 'required|date',
            'metadata' => 'nullable|array',
        ];
    }
}
