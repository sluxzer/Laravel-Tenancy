<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Currency Controller (Admin)
 *
 * Platform-level currency management.
 */
class CurrencyController extends Controller
{
    /**
     * Get all currencies.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Currency::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $currencies = $query->orderBy('code')->get();

        return response()->json([
            'success' => true,
            'data' => $currencies,
        ]);
    }

    /**
     * Get a specific currency.
     */
    public function show(string $id): JsonResponse
    {
        $currency = Currency::with(['exchangeRatesFrom', 'exchangeRatesTo'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $currency,
        ]);
    }

    /**
     * Create a new currency.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:3|unique:currencies',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:3',
            'is_default' => 'boolean',
        ]);

        if (($validated['is_default'] ?? false) === true) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        $currency = Currency::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Currency created successfully',
            'data' => $currency,
        ], 201);
    }

    /**
     * Update a currency.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $currency = Currency::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'symbol' => 'sometimes|string|max:3',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (($validated['is_default'] ?? false) === true && ! $currency->is_default) {
            Currency::where('id', '!=', $currency->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $currency->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Currency updated successfully',
            'data' => $currency,
        ]);
    }

    /**
     * Delete a currency.
     */
    public function destroy(string $id): JsonResponse
    {
        $currency = Currency::findOrFail($id);

        if ($currency->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete default currency',
            ], 400);
        }

        $currency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Currency deleted successfully',
        ]);
    }

    /**
     * Get exchange rates for a currency.
     */
    public function exchangeRates(string $id): JsonResponse
    {
        $currency = Currency::with(['exchangeRatesFrom', 'exchangeRatesTo'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $currency->exchangeRatesFrom,
                'to' => $currency->exchangeRatesTo,
            ],
        ]);
    }
}
