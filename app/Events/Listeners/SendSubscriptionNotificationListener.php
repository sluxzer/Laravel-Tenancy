<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\SubscriptionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Send Subscription Notification Listener
 *
 * Sends email, SMS, and push notifications when subscription is created.
 */
class SendSubscriptionNotificationListener implements ShouldQueue
{
    public int $tries = 3;

    public function handle(SubscriptionCreated $event): void
    {
        $user = \App\Models\User::find($event->userId);
        if (!$user) {
            return;
        }

        $message = "Your subscription to {$event->planName} has been activated successfully!";

        try {
            Notification::route(
                $user->email,
                "mail.subscription_activated",
                $message
            );

            $this->info("Subscription notification sent to user {$event->userId} for subscription {$event->subscriptionId}");
        } catch (\Exception $e) {
            Log::error('Failed to send subscription notification', [
                'user_id' => $event->userId,
                'subscription_id' => $event->subscriptionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
