<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Handler Controller (Tenant)
 *
 * Handles incoming webhook events from external providers.
 */
class WebhookHandlerController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle Stripe webhooks.
     */
    public function stripe(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            Log::warning('Stripe webhook missing signature', [
                'tenant_id' => $tenant->id,
            ]);

            return response()->json(['success' => false], 400);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );

            // Trigger webhook to tenant's configured webhooks
            $this->webhookService->dispatchToTenantWebhooks(
                $tenant,
                'stripe.'.$event->type,
                $event->toArray()
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 400);
        }
    }

    /**
     * Handle Xendit webhooks.
     */
    public function xendit(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $payload = $request->all();
        $token = $request->header('Xendit-Notification-Token');

        // Verify webhook signature
        $expectedToken = hash_hmac(
            'sha256',
            json_encode($payload),
            config('services.xendit.api_key')
        );

        if ($token !== $expectedToken) {
            Log::warning('Xendit webhook invalid signature', [
                'tenant_id' => $tenant->id,
            ]);

            return response()->json(['success' => false], 400);
        }

        try {
            $eventType = $payload['event_type'] ?? 'unknown';
            $eventId = $payload['id'] ?? null;

            // Trigger webhook to tenant's configured webhooks
            $this->webhookService->dispatchToTenantWebhooks(
                $tenant,
                'xendit.'.$eventType,
                $payload
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Xendit webhook error', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 400);
        }
    }

    /**
     * Handle generic webhook.
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $payload = $request->all();
        $headers = $request->headers->all();

        try {
            // Trigger webhook to tenant's configured webhooks
            $this->webhookService->dispatchToTenantWebhooks(
                $tenant,
                $provider.'.webhook',
                [
                    'payload' => $payload,
                    'headers' => $headers,
                ]
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Generic webhook error', [
                'tenant_id' => $tenant->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 400);
        }
    }
}
