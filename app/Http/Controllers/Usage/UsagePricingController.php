<?php

declare(strict_types=1);

namespace App\Http\Controllers\Usage;

use App\Http\Controllers\Controller;
use App\Models\UsagePricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Usage Pricing Controller (Tenant)
 *
 * Tenant-level usage pricing management.
 */
class UsagePricingController extends Controller
{
    /**
     * Get all usage pricing for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = UsagePricing::where('tenant_id', $tenant->id);

        if ($request->has('metric_type')) {
            $query->where('metric_type', $request->input('metric_type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $pricing = $query->orderBy('metric_type')
            ->orderBy('min_quantity')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    /**
     * Get a specific usage pricing.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $pricing = UsagePricing::where('tenant_id', $tenant->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    /**
     * Create a new usage pricing.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'metric_type' => 'required|string|max:255',
            'min_quantity' => 'required|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0',
            'price_per_unit' => 'required|numeric|min:0',
            'currency_code' => 'required|string|max:3',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if (isset($validated['max_quantity']) && $validated['max_quantity'] <= $validated['min_quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Max quantity must be greater than min quantity',
            ], 422);
        }

        $pricing = UsagePricing::create([
            'tenant_id' => $tenant->id,
            'metric_type' => $validated['metric_type'],
            'min_quantity' => $validated['min_quantity'],
            'max_quantity' => $validated['max_quantity'] ?? null,
            'price_per_unit' => $validated['price_per_unit'],
            'currency_code' => $validated['currency_code'],
            'is_active' => $validated['is_active'] ?? true,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usage pricing created successfully',
            'data' => $pricing,
        ], 201);
    }

    /**
     * Update a usage pricing.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $pricing = UsagePricing::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'min_quantity' => 'sometimes|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0',
            'price_per_unit' => 'sometimes|numeric|min:0',
            'currency_code' => 'sometimes|string|max:3',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if (isset($validated['min_quantity'], $validated['max_quantity'])
            && $validated['max_quantity'] <= $validated['min_quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Max quantity must be greater than min quantity',
            ], 422);
        }

        $pricing->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Usage pricing updated successfully',
            'data' => $pricing,
        ]);
    }

    /**
     * Delete a usage pricing.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $pricing = UsagePricing::where('tenant_id', $tenant->id)->findOrFail($id);
        $pricing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usage pricing deleted successfully',
        ]);
    }

    /**
     * Calculate cost for usage.
     */
    public function calculate(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'metric_type' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
        ]);

        $pricing = UsagePricing::where('tenant_id', $tenant->id)
            ->where('metric_type', $validated['metric_type'])
            ->where('is_active', true)
            ->orderBy('min_quantity', 'desc')
            ->get();

        $totalCost = 0;
        $remainingQuantity = $validated['quantity'];
        $breakdown = [];

        foreach ($pricing as $tier) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $tierMin = $tier->min_quantity;
            $tierMax = $tier->max_quantity ?? PHP_INT_MAX;
            $tierStart = max($tierMin, $remainingQuantity - ($remainingQuantity - $tierMin));

            if ($remainingQuantity > $tierMin) {
                $tierQuantity = min($remainingQuantity - $tierMin, $tierMax - $tierMin);
                $tierCost = $tierQuantity * $tier->price_per_unit;
                $totalCost += $tierCost;
                $remainingQuantity = $tierMin;

                $breakdown[] = [
                    'tier' => $tier->id,
                    'min_quantity' => $tier->min_quantity,
                    'max_quantity' => $tier->max_quantity,
                    'quantity' => $tierQuantity,
                    'price_per_unit' => $tier->price_per_unit,
                    'cost' => $tierCost,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'metric_type' => $validated['metric_type'],
                'quantity' => $validated['quantity'],
                'total_cost' => round($totalCost, 2),
                'currency_code' => $pricing->first()?->currency_code ?? 'USD',
                'breakdown' => $breakdown,
            ],
        ]);
    }
}
