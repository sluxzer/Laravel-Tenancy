<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\NewAccessToken;

/**
 * Tenant Auth Controller
 *
 * Handles tenant-specific authentication operations.
 */
class TenantAuthController extends Controller
{
    /**
     * Get authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load(['roles', 'permissions']),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$request->user()->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Revoke all existing tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Logout from all devices.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices',
        ]);
    }

    /**
     * Get user tokens.
     */
    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()
            ->tokens()
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'expires_at' => $token->expires_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }

    /**
     * Create API token.
     */
    public function createToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date',
        ]);

        /** @var NewAccessToken $token */
        $token = $request()->user()->createToken(
            $validated['name'],
            $validated['abilities'] ?? ['*'],
            $validated['expires_at'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Token created successfully',
            'data' => [
                'token' => $token->plainTextToken,
                'abilities' => $token->accessToken->abilities,
                'expires_at' => $token->accessToken->expires_at,
            ],
        ], 201);
    }

    /**
     * Delete API token.
     */
    public function deleteToken(Request $request, string $tokenId): JsonResponse
    {
        $token = $request->user()
            ->tokens()
            ->where('id', $tokenId)
            ->firstOrFail();

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token deleted successfully',
        ]);
    }

    /**
     * Get user roles and permissions.
     */
    public function rolesAndPermissions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->allPermissions()->pluck('name'),
            ],
        ]);
    }

    /**
     * Check if user has permission.
     */
    public function hasPermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permission' => 'required|string',
        ]);

        $hasPermission = $request->user()->can($validated['permission']);

        return response()->json([
            'success' => true,
            'data' => [
                'permission' => $validated['permission'],
                'has_permission' => $hasPermission,
            ],
        ]);
    }

    /**
     * Check if user has role.
     */
    public function hasRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|string',
        ]);

        $hasRole = $request->user()->hasRole($validated['role']);

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $validated['role'],
                'has_role' => $hasRole,
            ],
        ]);
    }
}
