<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\CheckSubscriptionExpiryJobCompleted;
use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Subscription Expiry Listener
 *
 * Sends notifications when subscription is about to expire.
 */
class SubscriptionExpiryListener implements ShouldQueue
{
    public int $tries = 3;

    public function handle(CheckSubscriptionExpiryJobCompleted $event): void
    {
        $subscription = $event->subscription;

        if (!$subscription) {
            return;
        }

        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->send($subscription, [
            'days_remaining' => $event->daysRemaining,
        'expires_at' => $subscription->current_period_end,
        ]);

        $this->info("Subscription expiry notification sent for subscription {$subscription->id}");
    }
}
