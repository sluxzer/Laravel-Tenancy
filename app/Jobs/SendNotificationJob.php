<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Send Notification Job
 *
 * Queue job for sending notifications via email, SMS, and push.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public array $channels = ['email', 'push'],
    ) {
        $this->notification = $notification;
        $this->channels = $channels;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $notificationService->send($this->notification, $this->channels);

        $this->notification->update(['status' => 'sent', 'sent_at' => now()]);
    }
}
