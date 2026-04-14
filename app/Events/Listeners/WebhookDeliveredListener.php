<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\WebhookDelivered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Delivered Listener
 *
 * Broadcasts event when a webhook is successfully delivered.
 */
class WebhookDeliveredListener implements ShouldQueue
{
    public int $tries = 5;

    public function handle(WebhookDelivered $event): void
    {
        $webhookEvent = $event->webhookEvent;
        $webhook = $webhookEvent->webhook;
        $tenant = tenancy()->tenant;

        if (! $webhook || ! $tenant) {
            return;
        }

        // Update webhook event status
        $webhookEvent->update(['status' => 'delivered', 'delivered_at' => now()]);

        Log::info("Webhook {$webhook->id} delivered to endpoint: {$webhook->url}");
    }
}
