<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Webhook Controller (Tenant)
 *
 * Tenant-level webhook configuration management.
 */
class WebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Get all webhooks for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Webhook::where('tenant_id', $tenant->id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('event')) {
            $query->whereJsonContains('events', $request->input('event'));
        }

        $webhooks = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $webhooks->items(),
            'pagination' => [
                'total' => $webhooks->total(),
                'per_page' => $webhooks->perPage(),
                'current_page' => $webhooks->currentPage(),
                'last_page' => $webhooks->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific webhook.
     */
    public function show(string $webhook): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $webhook = Webhook::where('tenant_id', $tenant->id)
            ->with(['events'])
            ->findOrFail($webhook);

        return response()->json([
            'success' => true,
            'data' => $webhook,
        ]);
    }

    /**
     * Create a new webhook.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'events' => 'required|array',
            'events.*' => 'string',
            'secret' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $webhook = $this->webhookService->create(
            $tenant,
            $validated['name'],
            $validated['url'],
            $validated['events'],
            $validated['secret'] ?? null,
            $validated['is_active'] ?? true
        );

        return response()->json([
            'success' => true,
            'message' => 'Webhook created successfully',
            'data' => $webhook,
        ], 201);
    }

    /**
     * Update a webhook.
     */
    public function update(Request $request, string $webhook): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $webhook = Webhook::where('tenant_id', $tenant->id)->findOrFail($webhook);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|max:2048',
            'events' => 'sometimes|array',
            'events.*' => 'string',
            'secret' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $webhook = $this->webhookService->update($webhook, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Webhook updated successfully',
            'data' => $webhook,
        ]);
    }

    /**
     * Delete a webhook.
     */
    public function destroy(string $webhook): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $webhook = Webhook::where('tenant_id', $tenant->id)->findOrFail($webhook);
        $this->webhookService->delete($webhook);

        return response()->json([
            'success' => true,
            'message' => 'Webhook deleted successfully',
        ]);
    }

    /**
     * Toggle webhook status.
     */
    public function toggle(string $webhook): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $webhook = Webhook::where('tenant_id', $tenant->id)->findOrFail($webhook);
        $webhook->update(['is_active' => ! $webhook->is_active]);

        return response()->json([
            'success' => true,
            'message' => $webhook->is_active ? 'Webhook enabled' : 'Webhook disabled',
            'data' => $webhook,
        ]);
    }

    /**
     * Test a webhook.
     */
    public function test(string $webhook): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $webhook = Webhook::where('tenant_id', $tenant->id)->findOrFail($webhook);

        $result = $this->webhookService->test($webhook);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result,
        ]);
    }

    /**
     * Regenerate webhook secret.
     */
    public function regenerateSecret(string $webhook): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $webhook = Webhook::where('tenant_id', $tenant->id)->findOrFail($webhook);
        $webhook->update([
            'secret' => Str::random(32),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook secret regenerated successfully',
            'data' => [
                'id' => $webhook->id,
                'secret' => $webhook->secret,
            ],
        ]);
    }

    /**
     * Get webhook events.
     */
    public function events(Request $request, string $webhook): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $webhook = Webhook::where('tenant_id', $tenant->id)->findOrFail($webhook);

        $events = $webhook->events()
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'pagination' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }

    /**
     * Retry failed webhook event.
     */
    public function retryEvent(string $webhook, string $eventId): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $webhook = Webhook::where('tenant_id', $tenant->id)->findOrFail($webhook);
        $event = $webhook->events()->findOrFail($eventId);

        $result = $this->webhookService->retryEvent($event);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['event'] ?? null,
        ]);
    }
}
