<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Service
 *
 * Handles webhook configuration and event delivery.
 */
class WebhookService
{
    /**
     * Create a webhook.
     */
    public function createWebhook(Tenant $tenant, array $data): Webhook
    {
        $secret = Str::random(64);

        return Webhook::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'url' => $data['url'],
            'secret' => $secret,
            'events' => $data['events'] ?? [],
            'headers' => $data['headers'] ?? [],
            'is_active' => true,
        ]);
    }

    /**
     * Trigger a webhook event.
     */
    public function triggerWebhook(Webhook $webhook, string $eventName, array $payload): WebhookEvent
    {
        return WebhookEvent::create([
            'webhook_id' => $webhook->id,
            'event_name' => $eventName,
            'payload' => json_encode($payload),
            'status_code' => 200,
            'response' => null,
            'retry_count' => 0,
            'delivered_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * Deliver webhook to configured URL.
     */
    public function deliverWebhook(WebhookEvent $event): bool
    {
        $webhook = $event->webhook;

        if (! $webhook->is_active) {
            return false;
        }

        try {
            $response = Http::post($webhook->url, [
                'events' => [$event->event_name],
                'data' => json_decode($event->payload, true),
                'timestamp' => Carbon::now()->timestamp,
                'headers' => $webhook->headers ?? [],
            ]);

            $status = $response->successful();

            WebhookEvent::where('id', $event->id)->update([
                'status_code' => $response->status(),
                'response' => json_encode($response->json()),
                'delivered_at' => $status ? Carbon::now()->toDateTimeString() : null,
                'retry_count' => DB::raw('retry_count + 1'),
            ]);

            return $status;
        } catch (\Exception $e) {
            Log::error('Webhook delivery failed', [
                'webhook' => $webhook->name,
                'url' => $webhook->url,
                'event' => $event->event_name,
                'error' => $e->getMessage(),
            ]);

            WebhookEvent::where('id', $event->id)->update([
                'status_code' => 500,
                'response' => json_encode(['error' => $e->getMessage()]),
                'retry_count' => DB::raw('retry_count + 1'),
            ]);

            return false;
        }
    }

    /**
     * Retry failed webhook event.
     */
    public function retryWebhook(WebhookEvent $event): bool
    {
        $webhook = $event->webhook;

        if ($webhook->is_active && $event->retry_count < 5) {
            return $this->deliverWebhook($event);
        }

        return false;
    }

    /**
     * Get active webhooks for tenant.
     */
    public function getActiveWebhooks(Tenant $tenant): Collection
    {
        return Webhook::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get webhook events for a webhook.
     */
    public function getWebhookEvents(Webhook $webhook, int $limit = 100): Collection
    {
        return WebhookEvent::where('webhook_id', $webhook->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
