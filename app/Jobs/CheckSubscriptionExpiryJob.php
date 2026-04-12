<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Check Subscription Expiry Job
 *
 * Queue job for checking and handling expiring subscriptions.
 */
class CheckSubscriptionExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public int $daysBeforeExpiry = 3,
    ) {
        $this->subscription = $subscription;
        $this->daysBeforeExpiry = $daysBeforeExpiry;
    }

    /**
     * Execute the job.
     */
    public function handle(SubscriptionService $subscriptionService): void
    {
        // Check if subscription is expiring soon
        if ($subscription->current_period_end) {
            $daysUntilExpiry = now()->diffInDays($subscription->current_period_end);

            if ($daysUntilExpiry <= $this->daysBeforeExpiry && $daysUntilExpiry > 0) {
                $subscriptionService->sendExpiryNotification($this->subscription, $daysUntilExpiry);
            }
        }
    }
}
