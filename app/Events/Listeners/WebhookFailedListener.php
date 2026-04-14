<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\WebhookFailed;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Failed Listener
 *
 * Broadcasts event when a webhook delivery fails.
 */
class WebhookFailedListener implements ShouldQueue
{
    public int $tries = 5;

    public function handle(WebhookFailed $event): void
    {
        $webhookEvent = $event->webhookEvent;
        $webhook = $webhookEvent->webhook;
        $tenant = tenancy()->tenant;

        if (! $webhook || ! $tenant) {
            return;
        }

        // Update webhook event and retry count
        $webhookEvent->update([
            'status' => 'failed',
            'error_message' => $webhookEvent->errorMessage ?? null,
            'retry_count' => ($webhookEvent->retry_count ?? 0) + 1,
        ]);

        $webhookService = app(WebhookService::class);
        $webhookService->logFailure($webhook, $webhookEvent, 'Webhook delivery failed: '.$webhookEvent->errorMessage);

        Log::error("Webhook {$webhook->id} failed to deliver: {$webhook->url}. Retry {$webhookEvent->retry_count}");
    }
}
