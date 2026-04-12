<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Process Subscription Renewal Job
 *
 * Queue job for processing automatic subscription renewals.
 */
class ProcessSubscriptionRenewalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
    ) {
        $this->subscription = $subscription;
    }

    /**
     * Execute the job.
     */
    public function handle(SubscriptionService $subscriptionService): void
    {
        $this->subscriptionService->renew($this->subscription);
    }
}
