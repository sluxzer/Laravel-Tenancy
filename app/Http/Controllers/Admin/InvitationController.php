<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Invitation Controller (Admin)
 *
 * Platform-level invitation management.
 */
class InvitationController extends Controller
{
    protected InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Get all invitations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invitation::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('email')) {
            $query->where('email', 'like', '%'.$request->input('email').'%');
        }

        $invitations = $query->with(['tenant', 'invitedBy'])
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
        $invitation = Invitation::with(['tenant', 'invitedBy', 'acceptedBy'])->findOrFail($id);

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
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'email' => 'required|email',
            'role' => 'required|string|in:admin,manager,member',
            'invited_by' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $invitation = $this->invitationService->create(
            $validated['tenant_id'],
            $validated['email'],
            $validated['role'],
            $validated['invited_by'],
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
        $invitation = Invitation::findOrFail($id);

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
        $invitation = Invitation::findOrFail($id);
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
        $invitation = Invitation::findOrFail($id);

        if ($invitation->status === 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot resend accepted invitation',
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
        $invitation = Invitation::findOrFail($id);

        if ($invitation->status === 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel accepted invitation',
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
     * Accept an invitation.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = $this->invitationService->accept(
            $invitation->token,
            $validated['user_id'],
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
}
