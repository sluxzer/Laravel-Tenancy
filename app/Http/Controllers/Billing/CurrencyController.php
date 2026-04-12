<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Currency Controller (Tenant)
 *
 * Tenant-level currency preferences.
 */
class CurrencyController extends Controller
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get available currencies.
     */
    public function index(): JsonResponse
    {
        $currencies = Currency::where('is_active', true)
            ->orderBy('code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $currencies,
        ]);
    }

    /**
     * Get exchange rate.
     */
    public function exchangeRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:3',
            'to' => 'required|string|max:3',
            'amount' => 'required|numeric|min:0',
        ]);

        $fromCurrency = Currency::where('code', strtoupper($validated['from']))->firstOrFail();
        $toCurrency = Currency::where('code', strtoupper($validated['to']))->firstOrFail();

        $rate = $this->currencyService->getExchangeRate(
            $fromCurrency,
            $toCurrency
        );

        if (! $rate) {
            return response()->json([
                'success' => false,
                'message' => 'Exchange rate not found',
            ], 404);
        }

        $converted = $this->currencyService->convertCurrency(
            (float) $validated['amount'],
            $fromCurrency,
            $toCurrency
        );

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $fromCurrency->code,
                'to' => $toCurrency->code,
                'amount' => $validated['amount'],
                'converted_amount' => round($converted, 2),
                'rate' => $rate->rate,
                'from_symbol' => $fromCurrency->symbol,
                'to_symbol' => $toCurrency->symbol,
            ],
        ]);
    }

    /**
     * Convert amount.
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from_currency' => 'required|string|max:3',
            'to_currency' => 'required|string|max:3',
        ]);

        $fromCurrency = Currency::where('code', strtoupper($validated['from_currency']))->firstOrFail();
        $toCurrency = Currency::where('code', strtoupper($validated['to_currency']))->firstOrFail();

        $converted = $this->currencyService->convertCurrency(
            (float) $validated['amount'],
            $fromCurrency,
            $toCurrency
        );

        return response()->json([
            'success' => true,
            'data' => [
                'amount' => $validated['amount'],
                'from_currency' => $fromCurrency->code,
                'to_currency' => $toCurrency->code,
                'converted_amount' => round($converted, 2),
                'from_symbol' => $fromCurrency->symbol,
                'to_symbol' => $toCurrency->symbol,
            ],
        ]);
    }
}
