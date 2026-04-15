<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\Webhook;
use App\Models\WebhookEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    /**
     * Create a webhook (controller interface).
     */
    public function create(Tenant $tenant, string $name, string $url, array $events, ?string $secret = null, bool $isActive = true, ?string $description = null): Webhook
    {
        return Webhook::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'url' => $url,
            'secret' => $secret ?? Str::random(64),
            'events' => $events,
            'headers' => ['Content-Type' => 'application/json'],
            'is_active' => $isActive,
        ]);
    }

    /**
     * Update a webhook.
     */
    public function update(Webhook $webhook, array $data): Webhook
    {
        $webhook->update(array_filter([
            'name' => $data['name'] ?? null,
            'url' => $data['url'] ?? null,
            'events' => $data['events'] ?? null,
            'secret' => $data['secret'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ]));

        return $webhook->fresh();
    }

    /**
     * Delete a webhook.
     */
    public function delete(Webhook $webhook): void
    {
        $webhook->delete();
    }

    /**
     * Test a webhook by sending a test payload.
     */
    public function test(Webhook $webhook): array
    {
        try {
            $response = Http::post($webhook->url, [
                'test' => true,
                'webhook_id' => $webhook->id,
                'timestamp' => Carbon::now()->timestamp,
                'message' => 'Test webhook delivery',
            ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Test webhook delivered successfully' : 'Test webhook failed',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Test webhook failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Retry a failed webhook event.
     */
    public function retryEvent(WebhookEvent $event): array
    {
        if ($event->retry_count >= 5) {
            return [
                'success' => false,
                'message' => 'Maximum retry attempts exceeded',
            ];
        }

        $success = $this->deliverWebhook($event);

        return [
            'success' => $success,
            'message' => $success ? 'Webhook event retried successfully' : 'Webhook event retry failed',
            'event' => $event->fresh(),
        ];
    }
}
