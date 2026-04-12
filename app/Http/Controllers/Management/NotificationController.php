<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification Controller (Tenant)
 *
 * Tenant-level notification management.
 */
class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Notification::where('tenant_id', $tenant->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $notifications = $query->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific notification.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $notification = Notification::where('tenant_id', $tenant->id)
            ->with(['user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $notification,
        ]);
    }

    /**
     * Create a new notification.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'send_push' => 'boolean',
            'scheduled_at' => 'nullable|date',
        ]);

        $notification = $this->notificationService->create(
            $tenant,
            $validated['type'],
            $validated['title'],
            $validated['message'],
            $validated['user_id'] ?? null,
            $validated['data'] ?? [],
            $validated['send_email'] ?? false,
            $validated['send_sms'] ?? false,
            $validated['send_push'] ?? false,
            $validated['scheduled_at'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => $notification,
        ], 201);
    }

    /**
     * Update a notification.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $notification = Notification::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,sent,delivered,failed',
            'title' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
            'data' => 'nullable|array',
        ]);

        $notification->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Notification updated successfully',
            'data' => $notification,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $notification = Notification::where('tenant_id', $tenant->id)->findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $notification = Notification::where('tenant_id', $tenant->id)->findOrFail($id);
        $notification->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification,
        ]);
    }

    /**
     * Mark all notifications as read for user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get unread count for user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    /**
     * Send notification.
     */
    public function send(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $notification = Notification::where('tenant_id', $tenant->id)->findOrFail($id);

        $result = $this->notificationService->send($notification);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully',
            'data' => $result['notification'],
        ]);
    }

    /**
     * Bulk create notifications.
     */
    public function bulkSend(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
            'send_email' => 'boolean',
            'send_push' => 'boolean',
        ]);

        $notifications = [];
        foreach ($validated['user_ids'] as $userId) {
            $notification = $this->notificationService->create(
                $tenant,
                $validated['type'],
                $validated['title'],
                $validated['message'],
                $userId,
                $validated['data'] ?? [],
                $validated['send_email'] ?? false,
                false,
                $validated['send_push'] ?? false
            );
            $notifications[] = $notification;
        }

        return response()->json([
            'success' => true,
            'message' => count($notifications).' notifications created successfully',
            'data' => $notifications,
        ], 201);
    }
}
