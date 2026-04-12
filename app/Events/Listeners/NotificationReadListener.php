<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\NotificationRead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notification Read Listener
 *
 * Broadcasts event when a notification is read by user.
 */
class NotificationReadListener implements ShouldQueue
{
    public int $tries = 3;

    public function handle(NotificationRead $event): void
    {
        $notification = $event->notification;
        $user = \App\Models\User::find($event->userId);

        if (!$user) {
            return;
        }

        $notification->update(['read_at' => now()]);

        $this->info("Notification {$notification->id} marked as read by user {$event->userId}");
    }
}
