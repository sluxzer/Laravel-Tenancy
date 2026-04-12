<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Invitation Controller (Tenant)
 *
 * Tenant-level invitation management.
 */
class InvitationController extends Controller
{
    protected InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Get all invitations for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Invitation::where('tenant_id', $tenant->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('email')) {
            $query->where('email', 'like', '%'.$request->input('email').'%');
        }

        $invitations = $query->with(['invitedBy', 'acceptedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $invitations->items(),
            'pagination' => [
                'total' => $invitations->total(),
                'per_page' => $invitations->perPage(),
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific invitation.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invitation = Invitation::where('tenant_id', $tenant->id)
            ->with(['invitedBy', 'acceptedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $invitation,
        ]);
    }

    /**
     * Create a new invitation.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|string|in:admin,manager,member',
            'message' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        // Check if invitation already exists for this email
        $existingInvitation = Invitation::where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->first();

        if ($existingInvitation) {
            return response()->json([
                'success' => false,
                'message' => 'An invitation already exists for this email',
            ], 400);
        }

        $invitation = $this->invitationService->create(
            $tenant->id,
            $validated['email'],
            $validated['role'],
            $request->user()->id,
            $validated['message'] ?? null,
            $validated['metadata'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Invitation created successfully',
            'data' => $invitation,
        ], 201);
    }

    /**
     * Update an invitation.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invitation = Invitation::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($invitation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update invitation that is not pending',
            ], 400);
        }

        $validated = $request->validate([
            'role' => 'sometimes|string|in:admin,manager,member',
            'message' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $invitation->update([
            'role' => $validated['role'] ?? $invitation->role,
            'message' => $validated['message'] ?? $invitation->message,
            'metadata' => $validated['metadata'] ?? $invitation->metadata,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation updated successfully',
            'data' => $invitation,
        ]);
    }

    /**
     * Delete an invitation.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invitation = Invitation::where('tenant_id', $tenant->id)->findOrFail($id);
        $invitation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invitation deleted successfully',
        ]);
    }

    /**
     * Resend an invitation.
     */
    public function resend(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invitation = Invitation::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($invitation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot resend invitation that is not pending',
            ], 400);
        }

        $this->invitationService->resend($invitation);

        return response()->json([
            'success' => true,
            'message' => 'Invitation resent successfully',
            'data' => $invitation,
        ]);
    }

    /**
     * Cancel an invitation.
     */
    public function cancel(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invitation = Invitation::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($invitation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel invitation that is not pending',
            ], 400);
        }

        $invitation->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Invitation cancelled successfully',
            'data' => $invitation,
        ]);
    }

    /**
     * Accept an invitation (via token).
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = $this->invitationService->accept(
            $invitation->token,
            $request->user()->id,
            $validated['password']
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation accepted successfully',
            'data' => $result['user'],
        ]);
    }

    /**
     * Get invitation by token.
     */
    public function showByToken(string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'tenant_name' => $invitation->tenant->name,
                'message' => $invitation->message,
                'expires_at' => $invitation->expires_at,
            ],
        ]);
    }
}
