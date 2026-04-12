<?php

declare(strict_types=1);

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Invitation Request
 *
 * Validation request for creating a tenant invitation.
 */
class CreateInvitationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'role' => 'required|in:admin,manager,member',
            'message' => 'nullable|string',
        ];
    }
}
