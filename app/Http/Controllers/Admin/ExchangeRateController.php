<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exchange Rate Controller (Admin)
 *
 * Platform-level exchange rate management.
 */
class ExchangeRateController extends Controller
{
    /**
     * Get all exchange rates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExchangeRate::query();

        if ($request->has('from_currency_code')) {
            $query->whereHas('fromCurrency', fn ($q) => $q->where('code', $request->input('from_currency_code')));
        }

        if ($request->has('to_currency_code')) {
            $query->whereHas('toCurrency', fn ($q) => $q->where('code', $request->input('to_currency_code')));
        }

        $rates = $query->with(['fromCurrency', 'toCurrency'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $rates->items(),
            'pagination' => [
                'total' => $rates->total(),
                'per_page' => $rates->perPage(),
                'current_page' => $rates->currentPage(),
                'last_page' => $rates->lastPage(),
            ],
        ]);
    }

    /**
     * Get latest exchange rate between two currencies.
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id',
        ]);

        $rate = ExchangeRate::where('from_currency_id', $validated['from_currency_id'])
            ->where('to_currency_id', $validated['to_currency_id'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $rate) {
            return response()->json([
                'success' => false,
                'message' => 'Exchange rate not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rate->load(['fromCurrency', 'toCurrency']),
        ]);
    }

    /**
     * Create a new exchange rate.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id',
            'rate' => 'required|numeric|min:0',
            'source' => 'nullable|string|max:255',
        ]);

        $rate = ExchangeRate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate created successfully',
            'data' => $rate->load(['fromCurrency', 'toCurrency']),
        ], 201);
    }

    /**
     * Update an exchange rate.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $rate = ExchangeRate::findOrFail($id);

        $validated = $request->validate([
            'rate' => 'sometimes|numeric|min:0',
            'source' => 'nullable|string|max:255',
        ]);

        $rate->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate updated successfully',
            'data' => $rate->load(['fromCurrency', 'toCurrency']),
        ]);
    }

    /**
     * Delete an exchange rate.
     */
    public function destroy(string $id): JsonResponse
    {
        $rate = ExchangeRate::findOrFail($id);
        $rate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate deleted successfully',
        ]);
    }

    /**
     * Bulk import exchange rates.
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*.from_currency_id' => 'required|exists:currencies,id',
            'rates.*.to_currency_id' => 'required|exists:currencies,id',
            'rates.*.rate' => 'required|numeric|min:0',
            'rates.*.source' => 'nullable|string|max:255',
        ]);

        ExchangeRate::insert(array_map(fn ($rate) => [
            'from_currency_id' => $rate['from_currency_id'],
            'to_currency_id' => $rate['to_currency_id'],
            'rate' => $rate['rate'],
            'source' => $rate['source'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $validated['rates']));

        return response()->json([
            'success' => true,
            'message' => count($validated['rates']).' exchange rates imported successfully',
        ], 201);
    }
}
