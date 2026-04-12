<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Profile Request
 *
 * Validation request for updating user profile.
 */
class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.Auth::user()?->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
        ];
    }
}
