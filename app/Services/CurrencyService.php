<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Collection;

/**
 * Currency Service
 *
 * Handles currency conversion and exchange rate management.
 */
class CurrencyService
{
    /**
     * Convert amount from one currency to another.
     */
    public function convertCurrency(Currency $fromCurrency, Currency $toCurrency, float $amount): ?float
    {
        $exchangeRate = ExchangeRate::where('from_currency_id', $fromCurrency->id)
            ->where('to_currency_id', $toCurrency->id)
            ->where('is_active', true)
            ->first();

        if (! $exchangeRate) {
            throw new \Exception("Exchange rate not found for {$fromCurrency->code} to {$toCurrency->code}");
        }

        return $amount * $exchangeRate->rate;
    }

    /**
     * Get all active currencies.
     */
    public function getActiveCurrencies(): Collection
    {
        return Currency::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get default currency for tenant.
     */
    public function getTenantDefaultCurrency(Tenant $tenant): ?Currency
    {
        return $tenant->currency()->first();
    }

    /**
     * Update exchange rates from API.
     */
    public function fetchExchangeRates(): int
    {
        // This would call an external API
        // For now, return count of updated rates
        return ExchangeRate::where('is_active', true)->update(['fetched_at' => now()]);
    }
}
