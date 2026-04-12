<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification Preference Controller (Tenant)
 *
 * Tenant-level notification preference management.
 */
class NotificationPreferenceController extends Controller
{
    /**
     * Get notification preferences for user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = tenancy()->tenant;

        $preferences = NotificationPreference::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Get a specific notification preference.
     */
    public function show(string $id): JsonResponse
    {
        $user = $request->user();
        $tenant = tenancy()->tenant;

        $preference = NotificationPreference::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $preference,
        ]);
    }

    /**
     * Create or update notification preference.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'notification_type' => 'required|string|max:255',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
        ]);

        $preference = NotificationPreference::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'notification_type' => $validated['notification_type'],
            ],
            [
                'email_enabled' => $validated['email_enabled'] ?? true,
                'sms_enabled' => $validated['sms_enabled'] ?? false,
                'push_enabled' => $validated['push_enabled'] ?? true,
                'in_app_enabled' => $validated['in_app_enabled'] ?? true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification preference saved successfully',
            'data' => $preference,
        ], 201);
    }

    /**
     * Update a notification preference.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $tenant = tenancy()->tenant;

        $preference = NotificationPreference::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
        ]);

        $preference->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Notification preference updated successfully',
            'data' => $preference,
        ]);
    }

    /**
     * Delete a notification preference.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = $request->user();
        $tenant = tenancy()->tenant;

        $preference = NotificationPreference::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->findOrFail($id);
        $preference->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification preference deleted successfully',
        ]);
    }

    /**
     * Get global notification preferences.
     */
    public function globalPreferences(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $preferences = NotificationPreference::where('tenant_id', $tenant->id)
            ->whereNull('user_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Update global notification preference.
     */
    public function updateGlobal(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'notification_type' => 'required|string|max:255',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
        ]);

        $preference = NotificationPreference::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'notification_type' => $validated['notification_type'],
            ],
            [
                'email_enabled' => $validated['email_enabled'] ?? true,
                'sms_enabled' => $validated['sms_enabled'] ?? false,
                'push_enabled' => $validated['push_enabled'] ?? true,
                'in_app_enabled' => $validated['in_app_enabled'] ?? true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Global notification preference saved successfully',
            'data' => $preference,
        ]);
    }

    /**
     * Bulk update notification preferences.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string|max:255',
            'preferences.*.email_enabled' => 'boolean',
            'preferences.*.sms_enabled' => 'boolean',
            'preferences.*.push_enabled' => 'boolean',
            'preferences.*.in_app_enabled' => 'boolean',
        ]);

        foreach ($validated['preferences'] as $pref) {
            NotificationPreference::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'notification_type' => $pref['notification_type'],
                ],
                [
                    'email_enabled' => $pref['email_enabled'] ?? true,
                    'sms_enabled' => $pref['sms_enabled'] ?? false,
                    'push_enabled' => $pref['push_enabled'] ?? true,
                    'in_app_enabled' => $pref['in_app_enabled'] ?? true,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
        ]);
    }
}
