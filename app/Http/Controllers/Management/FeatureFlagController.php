<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feature Flag Controller (Tenant)
 *
 * Tenant-level feature flag management.
 */
class FeatureFlagController extends Controller
{
    /**
     * Get all feature flags for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        // Return platform-level flags that apply to this tenant
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

        // Check which flags are enabled for this tenant
        $flags->transform(function ($flag) use ($tenant) {
            $flag['is_enabled_for_tenant'] = $flag->isEnabledForTenant($tenant->id);

            return $flag;
        });

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
        $tenant = tenancy()->tenant;

        $flag = FeatureFlag::findOrFail($id);
        $flag['is_enabled_for_tenant'] = $flag->isEnabledForTenant($tenant->id);

        return response()->json([
            'success' => true,
            'data' => $flag,
        ]);
    }

    /**
     * Check if a feature flag is enabled.
     */
    public function check(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        $flag = FeatureFlag::where('key', $validated['key'])->first();

        if (! $flag) {
            return response()->json([
                'success' => false,
                'message' => 'Feature flag not found',
            ], 404);
        }

        $isEnabled = $flag->isEnabledForTenant($tenant->id, $request->user()->email ?? null);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $flag->key,
                'name' => $flag->name,
                'is_enabled' => $isEnabled,
                'type' => $flag->type,
                'description' => $flag->description,
            ],
        ]);
    }

    /**
     * Batch check multiple feature flags.
     */
    public function batchCheck(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'keys' => 'required|array',
            'keys.*' => 'string',
        ]);

        $flags = FeatureFlag::whereIn('key', $validated['keys'])->get();

        $result = [];
        foreach ($validated['keys'] as $key) {
            $flag = $flags->firstWhere('key', $key);
            $result[$key] = $flag
                ? $flag->isEnabledForTenant($tenant->id, $request->user()->email ?? null)
                : false;
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get enabled flags for tenant.
     */
    public function enabled(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $allFlags = FeatureFlag::where('is_active', true)->get();

        $enabledFlags = $allFlags->filter(function ($flag) use ($tenant) {
            return $flag->isEnabledForTenant($tenant->id);
        });

        return response()->json([
            'success' => true,
            'data' => $enabledFlags->map(fn ($flag) => [
                'key' => $flag->key,
                'name' => $flag->name,
                'type' => $flag->type,
                'description' => $flag->description,
            ]),
        ]);
    }
}
