<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Plan Controller (Admin)
 *
 * Platform-level plan management.
 */
class PlanController extends Controller
{
    /**
     * Get all plans.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Plan::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('billing_cycle')) {
            $query->where('billing_cycle', $request->input('billing_cycle'));
        }

        $plans = $query->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Get a specific plan.
     */
    public function show(string $id): JsonResponse
    {
        $plan = Plan::with(['features', 'prices'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $plan,
        ]);
    }

    /**
     * Create a new plan.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'billing_cycle' => 'required|in:monthly,yearly,quarterly',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'nullable|numeric|min:0',
            'trial_days' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'max_users' => 'nullable|integer|min:0',
            'max_storage_mb' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        $plan = Plan::create($validated);

        if (! empty($validated['features'])) {
            $plan->features()->createMany(
                array_map(fn ($feature) => ['feature' => $feature], $validated['features'])
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully',
            'data' => $plan->load('features'),
        ], 201);
    }

    /**
     * Update a plan.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:plans,slug,'.$plan->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'billing_cycle' => 'sometimes|in:monthly,yearly,quarterly',
            'price_monthly' => 'sometimes|numeric|min:0',
            'price_yearly' => 'nullable|numeric|min:0',
            'trial_days' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'max_users' => 'nullable|integer|min:0',
            'max_storage_mb' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully',
            'data' => $plan->load('features'),
        ]);
    }

    /**
     * Delete a plan.
     */
    public function destroy(string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        // Check if plan has active subscriptions
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete plan with active subscriptions',
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully',
        ]);
    }

    /**
     * Get plan features.
     */
    public function features(string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $plan->features()->get(),
        ]);
    }

    /**
     * Add feature to plan.
     */
    public function addFeature(Request $request, string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'feature' => 'required|string',
        ]);

        $feature = $plan->features()->create([
            'feature' => $validated['feature'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feature added successfully',
            'data' => $feature,
        ], 201);
    }

    /**
     * Remove feature from plan.
     */
    public function removeFeature(string $id, string $featureId): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $feature = $plan->features()->findOrFail($featureId);
        $feature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feature removed successfully',
        ]);
    }
}
