<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gdpr;

use App\Http\Controllers\Controller;
use App\Models\GdprDeletionRequest;
use App\Models\User;
use App\Services\GdprService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GDPR Controller (Tenant)
 *
 * Handles GDPR compliance operations for tenant data.
 */
class GdprController extends Controller
{
    protected GdprService $gdprService;

    public function __construct(GdprService $gdprService)
    {
        $this->gdprService = $gdprService;
    }

    /**
     * Get user's data (Data Portability).
     */
    public function exportUserData(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = tenancy()->tenant;

        $data = $this->gdprService->exportUserData($user, $tenant);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'data' => $data,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Download user data as file.
     */
    public function downloadUserData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|in:json,csv',
        ]);

        $user = $request->user();
        $tenant = tenancy()->tenant;

        $fileInfo = $this->gdprService->exportUserDataAsFile($user, $tenant, $validated['format']);

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $fileInfo['url'],
                'file_name' => $fileInfo['name'],
                'format' => $validated['format'],
            ],
        ]);
    }

    /**
     * Request account deletion.
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string',
            'confirmation' => 'required|accepted',
        ]);

        $user = $request->user();
        $tenant = tenancy()->tenant;

        $request = $this->gdprService->createDeletionRequest(
            $user,
            $tenant,
            $validated['reason'] ?? null
        );

        // Send confirmation email
        $this->gdprService->sendDeletionConfirmationEmail($request);

        return response()->json([
            'success' => true,
            'message' => 'Account deletion request submitted. Check your email for confirmation.',
            'data' => [
                'request_id' => $request->id,
                'status' => $request->status,
            ],
        ], 201);
    }

    /**
     * Confirm deletion request.
     */
    public function confirmDeletion(string $token): JsonResponse
    {
        $result = $this->gdprService->confirmDeletionRequest($token);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Account deletion confirmed. Your account will be permanently deleted within 30 days.',
            'data' => [
                'request_id' => $result['request']->id,
                'status' => $result['request']->status,
                'scheduled_deletion_at' => $result['request']->scheduled_deletion_at,
            ],
        ]);
    }

    /**
     * Cancel deletion request.
     */
    public function cancelDeletion(Request $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->gdprService->cancelDeletionRequest($user);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deletion request cancelled successfully',
        ]);
    }

    /**
     * Get deletion request status.
     */
    public function deletionStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        $request = GdprDeletionRequest::where('user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_pending_request' => false,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_pending_request' => true,
                'request_id' => $request->id,
                'status' => $request->status,
                'created_at' => $request->created_at,
                'scheduled_deletion_at' => $request->scheduled_deletion_at,
            ],
        ]);
    }

    /**
     * Get tenant data export (for administrators).
     */
    public function exportTenantData(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'format' => 'required|in:json,csv',
        ]);

        $fileInfo = $this->gdprService->exportTenantData($tenant, $validated['format']);

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $fileInfo['url'],
                'file_name' => $fileInfo['name'],
                'format' => $validated['format'],
            ],
        ]);
    }

    /**
     * Anonymize user data.
     */
    public function anonymize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'confirmation' => 'required|accepted',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $tenant = tenancy()->tenant;

        $result = $this->gdprService->anonymizeUser($user, $tenant);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'User data anonymized successfully',
        ]);
    }

    /**
     * Get GDPR consent status.
     */
    public function consentStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'marketing_consent' => $user->marketing_consent ?? false,
                'analytics_consent' => $user->analytics_consent ?? true,
                'consent_updated_at' => $user->consent_updated_at ?? null,
            ],
        ]);
    }

    /**
     * Update GDPR consent.
     */
    public function updateConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketing_consent' => 'boolean',
            'analytics_consent' => 'boolean',
        ]);

        $user = $request->user();

        $user->update([
            'marketing_consent' => $validated['marketing_consent'],
            'analytics_consent' => $validated['analytics_consent'],
            'consent_updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consent preferences updated successfully',
            'data' => [
                'marketing_consent' => $user->marketing_consent,
                'analytics_consent' => $user->analytics_consent,
                'consent_updated_at' => $user->consent_updated_at,
            ],
        ]);
    }
}
