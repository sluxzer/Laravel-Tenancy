<?php

declare(strict_types=1);

namespace App\Http\Controllers\Usage;

use App\Http\Controllers\Controller;
use App\Models\UsageAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Usage Alert Controller (Tenant)
 *
 * Tenant-level usage alert management.
 */
class UsageAlertController extends Controller
{
    /**
     * Get all usage alerts for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = UsageAlert::where('tenant_id', $tenant->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('metric_type')) {
            $query->where('metric_type', $request->input('metric_type'));
        }

        $alerts = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $alerts->items(),
            'pagination' => [
                'total' => $alerts->total(),
                'per_page' => $alerts->perPage(),
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific usage alert.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $alert = UsageAlert::where('tenant_id', $tenant->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $alert,
        ]);
    }

    /**
     * Create a new usage alert.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'metric_type' => 'required|string|max:255',
            'threshold' => 'required|numeric',
            'threshold_type' => 'required|in:absolute,percentage',
            'comparison' => 'required|in:greater_than,less_than,equals',
            'notification_email' => 'nullable|email',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $alert = UsageAlert::create([
            'tenant_id' => $tenant->id,
            'metric_type' => $validated['metric_type'],
            'threshold' => $validated['threshold'],
            'threshold_type' => $validated['threshold_type'],
            'comparison' => $validated['comparison'],
            'notification_email' => $validated['notification_email'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'status' => 'active',
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usage alert created successfully',
            'data' => $alert,
        ], 201);
    }

    /**
     * Update a usage alert.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $alert = UsageAlert::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'threshold' => 'sometimes|numeric',
            'threshold_type' => 'sometimes|in:absolute,percentage',
            'comparison' => 'sometimes|in:greater_than,less_than,equals',
            'notification_email' => 'nullable|email',
            'is_active' => 'boolean',
            'status' => 'sometimes|in:active,triggered,dismissed',
            'description' => 'nullable|string',
        ]);

        $alert->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Usage alert updated successfully',
            'data' => $alert,
        ]);
    }

    /**
     * Delete a usage alert.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $alert = UsageAlert::where('tenant_id', $tenant->id)->findOrFail($id);
        $alert->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usage alert deleted successfully',
        ]);
    }

    /**
     * Trigger a usage alert.
     */
    public function trigger(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $alert = UsageAlert::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($alert->status === 'triggered') {
            return response()->json([
                'success' => false,
                'message' => 'Alert has already been triggered',
            ], 400);
        }

        $alert->update([
            'status' => 'triggered',
            'triggered_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usage alert triggered successfully',
            'data' => $alert,
        ]);
    }

    /**
     * Reset a triggered alert.
     */
    public function reset(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $alert = UsageAlert::where('tenant_id', $tenant->id)->findOrFail($id);

        $alert->update([
            'status' => 'active',
            'triggered_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usage alert reset successfully',
            'data' => $alert,
        ]);
    }

    /**
     * Check usage against alerts.
     */
    public function check(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'metric_type' => 'required|string|max:255',
            'current_value' => 'required|numeric',
            'total_value' => 'nullable|numeric',
        ]);

        $alerts = UsageAlert::where('tenant_id', $tenant->id)
            ->where('metric_type', $validated['metric_type'])
            ->where('is_active', true)
            ->where('status', 'active')
            ->get();

        $triggeredAlerts = [];

        foreach ($alerts as $alert) {
            $valueToCheck = $alert->threshold_type === 'percentage' && isset($validated['total_value'])
                ? ($validated['current_value'] / $validated['total_value']) * 100
                : $validated['current_value'];

            $shouldTrigger = match ($alert->comparison) {
                'greater_than' => $valueToCheck > $alert->threshold,
                'less_than' => $valueToCheck < $alert->threshold,
                'equals' => $valueToCheck == $alert->threshold,
                default => false,
            };

            if ($shouldTrigger) {
                $alert->update([
                    'status' => 'triggered',
                    'triggered_at' => now(),
                ]);
                $triggeredAlerts[] = $alert;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'checked_alerts' => $alerts->count(),
                'triggered_alerts' => $triggeredAlerts,
            ],
        ]);
    }
}
