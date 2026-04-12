<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Feature Flag Request
 *
 * Validation request for creating a platform-level feature flag.
 */
class CreateFeatureFlagRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required|string|max:255|unique:feature_flags,key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:boolean,percentage,tenant_list,whitelist',
            'is_active' => 'boolean',
            'percentage' => 'required_if:type==percentage|integer|min:0|max:100',
            'allowed_tenants' => 'nullable|array|required_if:type==tenant_list',
            'allowed_tenants.*' => 'integer|exists:tenants,id',
            'allowed_emails' => 'nullable|array|required_if:type==whitelist',
            'allowed_emails.*' => 'email',
        ];
    }
}
