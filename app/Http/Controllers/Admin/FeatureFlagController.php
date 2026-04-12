<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feature Flag Controller (Admin)
 *
 * Platform-level feature flag management.
 */
class FeatureFlagController extends Controller
{
    /**
     * Get all feature flags.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FeatureFlag::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('key', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }

        $flags = $query->orderBy('key')->get();

        return response()->json([
            'success' => true,
            'data' => $flags,
        ]);
    }

    /**
     * Get a specific feature flag.
     */
    public function show(string $id): JsonResponse
    {
        $flag = FeatureFlag::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $flag,
        ]);
    }

    /**
     * Create a new feature flag.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:feature_flags',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:boolean,percentage,tenant_list,whitelist',
            'is_active' => 'boolean',
            'percentage' => 'nullable|integer|min:0|max:100',
            'allowed_tenants' => 'nullable|array',
            'allowed_tenants.*' => 'exists:tenants,id',
            'allowed_emails' => 'nullable|array',
            'allowed_emails.*' => 'email',
            'metadata' => 'nullable|array',
        ]);

        // Validate type-specific fields
        if ($validated['type'] === 'percentage' && ! isset($validated['percentage'])) {
            return response()->json([
                'success' => false,
                'message' => 'Percentage is required for percentage type',
            ], 422);
        }

        if ($validated['type'] === 'tenant_list' && empty($validated['allowed_tenants'] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'Allowed tenants is required for tenant_list type',
            ], 422);
        }

        $flag = FeatureFlag::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'is_active' => $validated['is_active'] ?? true,
            'percentage' => $validated['percentage'] ?? null,
            'allowed_tenants' => $validated['allowed_tenants'] ?? [],
            'allowed_emails' => $validated['allowed_emails'] ?? [],
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feature flag created successfully',
            'data' => $flag,
        ], 201);
    }

    /**
     * Update a feature flag.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $flag = FeatureFlag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:boolean,percentage,tenant_list,whitelist',
            'is_active' => 'boolean',
            'percentage' => 'nullable|integer|min:0|max:100',
            'allowed_tenants' => 'nullable|array',
            'allowed_tenants.*' => 'exists:tenants,id',
            'allowed_emails' => 'nullable|array',
            'allowed_emails.*' => 'email',
            'metadata' => 'nullable|array',
        ]);

        $flag->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Feature flag updated successfully',
            'data' => $flag,
        ]);
    }

    /**
     * Delete a feature flag.
     */
    public function destroy(string $id): JsonResponse
    {
        $flag = FeatureFlag::findOrFail($id);
        $flag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feature flag deleted successfully',
        ]);
    }

    /**
     * Toggle a feature flag.
     */
    public function toggle(string $id): JsonResponse
    {
        $flag = FeatureFlag::findOrFail($id);
        $flag->update(['is_active' => ! $flag->is_active]);

        return response()->json([
            'success' => true,
            'message' => $flag->is_active ? 'Feature flag enabled' : 'Feature flag disabled',
            'data' => $flag,
        ]);
    }

    /**
     * Check if a feature flag is enabled for a tenant.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'tenant_id' => 'nullable|exists:tenants,id',
            'user_email' => 'nullable|email',
        ]);

        $flag = FeatureFlag::where('key', $validated['key'])->first();

        if (! $flag) {
            return response()->json([
                'success' => false,
                'message' => 'Feature flag not found',
            ], 404);
        }

        $isEnabled = $flag->isEnabled(
            $validated['tenant_id'] ?? null,
            $validated['user_email'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $flag->key,
                'name' => $flag->name,
                'is_enabled' => $isEnabled,
                'type' => $flag->type,
            ],
        ]);
    }
}
