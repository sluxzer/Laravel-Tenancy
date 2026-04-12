<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant Controller (Admin)
 *
 * Platform-level tenant management.
 */
class TenantController extends Controller
{
    /**
     * Get all tenants.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('id', 'like', "%{$search}%");
        }

        $tenants = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $tenants->items(),
            'pagination' => [
                'total' => $tenants->total(),
                'per_page' => $tenants->perPage(),
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific tenant.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = Tenant::with([
            'owner',
            'domains',
            'subscription.plan',
            'subscription',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tenant,
        ]);
    }

    /**
     * Create a new tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|max:255|unique:tenants',
            'name' => 'required|string|max:255',
            'email' => 'required|email|exists:users,email',
            'plan_id' => 'required|exists:plans,id',
            'trial_days' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        $tenant = Tenant::create([
            'id' => $validated['id'],
            'name' => $validated['name'],
            'owner_id' => null, // Will be set after finding the user
            'status' => 'active',
            'trial_ends_at' => isset($validated['trial_days'])
                ? now()->addDays($validated['trial_days'])
                : null,
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully',
            'data' => $tenant,
        ], 201);
    }

    /**
     * Update a tenant.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,suspended,cancelled',
            'trial_ends_at' => 'sometimes|nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $tenant->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully',
            'data' => $tenant,
        ]);
    }

    /**
     * Delete a tenant.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        // Soft delete - mark as cancelled
        $tenant->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully',
        ]);
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => 'Tenant suspended successfully',
            'data' => $tenant,
        ]);
    }

    /**
     * Activate a tenant.
     */
    public function activate(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Tenant activated successfully',
            'data' => $tenant,
        ]);
    }

    /**
     * Get tenant statistics.
     */
    public function stats(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $stats = [
            'users_count' => $tenant->users()->count(),
            'active_users_count' => $tenant->users()->where('is_active', true)->count(),
            'subscriptions_count' => $tenant->subscriptions()->count(),
            'active_subscription' => $tenant->subscriptions()->where('status', 'active')->first(),
            'invoices_count' => 0, // Would need to query tenant's database
            'total_revenue' => 0, // Would need to calculate from invoices
            'created_at' => $tenant->created_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get tenant users.
     */
    public function users(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $users = $tenant->users()->with(['roles'])->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }
}
