<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Notification Service
 *
 * Handles notification creation and delivery.
 */
class NotificationService
{
    /**
     * Create a notification.
     */
    public function createNotification(array $data): Notification
    {
        return Notification::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'data' => $data['data'] ?? [],
            'is_read' => false,
            'sent_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->update([
            'is_read' => true,
            'read_at' => Carbon::now()->toDateTimeString(),
        ]);

        return $notification->fresh();
    }

    /**
     * Mark all notifications for user as read.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->update(['is_read' => true]);
    }

    /**
     * Get unread notification count for user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get notifications for user.
     */
    public function getUserNotifications(User $user, int $limit = 20, int $offset = 0): Collection
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Send notification to multiple users.
     */
    public function sendBulkNotification(array $userIds, array $data): void
    {
        $notifications = [];
        foreach ($userIds as $userId) {
            $notifications[] = array_merge($data, ['user_id' => $userId]);
        }

        Notification::insert($notifications);
    }

    /**
     * Get notification preferences for user.
     */
    public function getUserPreferences(User $user): Collection
    {
        return NotificationPreference::where('user_id', $user->id)
            ->where('is_enabled', true)
            ->pluck('type');
    }

    /**
     * Update notification preferences for user.
     */
    public function updatePreferences(User $user, array $preferences): void
    {
        $userId = $user->id;

        foreach ($preferences as $type => $enabled) {
            NotificationPreference::updateOrInsert([
                'user_id' => $userId,
                'type' => $type,
                'is_enabled' => $enabled,
            ]);
        }
    }
}
