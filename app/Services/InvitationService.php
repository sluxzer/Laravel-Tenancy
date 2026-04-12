<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invitation;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Invitation Service
 *
 * Handles user invitation management.
 */
class InvitationService
{
    /**
     * Create an invitation.
     */
    public function createInvitation(array $data): Invitation
    {
        return Invitation::create([
            'tenant_id' => $data['tenant_id'],
            'invited_by_user_id' => $data['invited_by_user_id'],
            'email' => $data['email'],
            'token' => Str::random(40),
            'role_id' => $data['role_id'],
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7)->toDateTimeString(),
        ]);
    }

    /**
     * Accept an invitation.
     */
    public function acceptInvitation(string $token, string $password, string $name): Invitation
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->with(['tenant.user', 'tenant.role'])
            ->firstOrFail();

        $user = User::create([
            'tenant_id' => $invitation->tenant_id,
            'name' => $name,
            'email' => $invitation->email,
            'password' => bcrypt($password),
        ]);

        // Assign role to user
        if ($invitation->role_id) {
            $user->roles()->sync([$invitation->role_id]);
        }

        $invitation->update([
            'status' => 'accepted',
            'user_id' => $user->id,
            'accepted_at' => Carbon::now()->toDateTimeString(),
        ]);

        return $user;
    }

    /**
     * Get pending invitations for tenant.
     */
    public function getPendingInvitations(Tenant $tenant): Collection
    {
        return Invitation::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete an invitation.
     */
    public function deleteInvitation(Invitation $invitation): bool
    {
        return $invitation->delete();
    }

    /**
     * Get invitation by token.
     */
    public function getInvitationByToken(string $token): ?Invitation
    {
        return Invitation::where('token', $token)
            ->with(['tenant.user', 'tenant.role'])
            ->first();
    }
}
