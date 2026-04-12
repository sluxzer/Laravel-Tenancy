<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TaxRate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tax Service
 *
 * Handles tax rate management and tax calculations.
 */
class TaxService
{
    /**
     * Common VAT/GST rates by country.
     */
    private const COUNTRY_TAX_RATES = [
        'US' => ['rate' => 0.0000, 'name' => 'No Tax (US)', 'type' => 'percentage'],
        'GB' => ['rate' => 0.2000, 'name' => 'VAT (UK)', 'type' => 'percentage'],
        'DE' => ['rate' => 0.1900, 'name' => 'VAT (Germany)', 'type' => 'percentage'],
        'FR' => ['rate' => 0.2000, 'name' => 'VAT (France)', 'type' => 'percentage'],
        'IT' => ['rate' => 0.2200, 'name' => 'VAT (Italy)', 'type' => 'percentage'],
        'ES' => ['rate' => 0.2100, 'name' => 'VAT (Spain)', 'type' => 'percentage'],
        'NL' => ['rate' => 0.2100, 'name' => 'VAT (Netherlands)', 'type' => 'percentage'],
        'BE' => ['rate' => 0.2100, 'name' => 'VAT (Belgium)', 'type' => 'percentage'],
        'AT' => ['rate' => 0.2000, 'name' => 'VAT (Austria)', 'type' => 'percentage'],
        'SE' => ['rate' => 0.2500, 'name' => 'VAT (Sweden)', 'type' => 'percentage'],
        'NO' => ['rate' => 0.2500, 'name' => 'VAT (Norway)', 'type' => 'percentage'],
        'DK' => ['rate' => 0.2500, 'name' => 'VAT (Denmark)', 'type' => 'percentage'],
        'FI' => ['rate' => 0.2550, 'name' => 'VAT (Finland)', 'type' => 'percentage'],
        'PL' => ['rate' => 0.2300, 'name' => 'VAT (Poland)', 'type' => 'percentage'],
        'CZ' => ['rate' => 0.2100, 'name' => 'VAT (Czech Republic)', 'type' => 'percentage'],
        'HU' => ['rate' => 0.2700, 'name' => 'VAT (Hungary)', 'type' => 'percentage'],
        'RO' => ['rate' => 0.1900, 'name' => 'VAT (Romania)', 'type' => 'percentage'],
        'BG' => ['rate' => 0.2000, 'name' => 'VAT (Bulgaria)', 'type' => 'percentage'],
        'GR' => ['rate' => 0.2400, 'name' => 'VAT (Greece)', 'type' => 'percentage'],
        'PT' => ['rate' => 0.2300, 'name' => 'VAT (Portugal)', 'type' => 'percentage'],
        'IE' => ['rate' => 0.2300, 'name' => 'VAT (Ireland)', 'type' => 'percentage'],
        'LU' => ['rate' => 0.1700, 'name' => 'VAT (Luxembourg)', 'type' => 'percentage'],
        'CH' => ['rate' => 0.0770, 'name' => 'VAT (Switzerland)', 'type' => 'percentage'],
        'CA' => ['rate' => 0.0500, 'name' => 'GST (Canada)', 'type' => 'percentage'],
        'AU' => ['rate' => 0.1000, 'name' => 'GST (Australia)', 'type' => 'percentage'],
        'NZ' => ['rate' => 0.1500, 'name' => 'GST (New Zealand)', 'type' => 'percentage'],
        'IN' => ['rate' => 0.1800, 'name' => 'GST (India)', 'type' => 'percentage'],
        'SG' => ['rate' => 0.0800, 'name' => 'GST (Singapore)', 'type' => 'percentage'],
        'MY' => ['rate' => 0.0600, 'name' => 'SST (Malaysia)', 'type' => 'percentage'],
        'TH' => ['rate' => 0.0700, 'name' => 'VAT (Thailand)', 'type' => 'percentage'],
        'VN' => ['rate' => 0.1000, 'name' => 'VAT (Vietnam)', 'type' => 'percentage'],
        'ID' => ['rate' => 0.1100, 'name' => 'VAT (Indonesia)', 'type' => 'percentage'],
        'PH' => ['rate' => 0.1200, 'name' => 'VAT (Philippines)', 'type' => 'percentage'],
        'JP' => ['rate' => 0.1000, 'name' => 'Consumption Tax (Japan)', 'type' => 'percentage'],
        'KR' => ['rate' => 0.1000, 'name' => 'VAT (South Korea)', 'type' => 'percentage'],
        'CN' => ['rate' => 0.1300, 'name' => 'VAT (China)', 'type' => 'percentage'],
        'HK' => ['rate' => 0.0000, 'name' => 'No Tax (Hong Kong)', 'type' => 'percentage'],
        'TW' => ['rate' => 0.0500, 'name' => 'VAT (Taiwan)', 'type' => 'percentage'],
        'AE' => ['rate' => 0.0500, 'name' => 'VAT (UAE)', 'type' => 'percentage'],
        'SA' => ['rate' => 0.1500, 'name' => 'VAT (Saudi Arabia)', 'type' => 'percentage'],
        'ZA' => ['rate' => 0.1500, 'name' => 'VAT (South Africa)', 'type' => 'percentage'],
        'NG' => ['rate' => 0.0750, 'name' => 'VAT (Nigeria)', 'type' => 'percentage'],
        'KE' => ['rate' => 0.1600, 'name' => 'VAT (Kenya)', 'type' => 'percentage'],
        'EG' => ['rate' => 0.1400, 'name' => 'VAT (Egypt)', 'type' => 'percentage'],
        'MX' => ['rate' => 0.1600, 'name' => 'VAT (Mexico)', 'type' => 'percentage'],
        'BR' => ['rate' => 0.1700, 'name' => 'ICMS (Brazil)', 'type' => 'percentage'],
        'AR' => ['rate' => 0.2100, 'name' => 'VAT (Argentina)', 'type' => 'percentage'],
        'CL' => ['rate' => 0.1900, 'name' => 'VAT (Chile)', 'type' => 'percentage'],
        'CO' => ['rate' => 0.1900, 'name' => 'VAT (Colombia)', 'type' => 'percentage'],
        'PE' => ['rate' => 0.1800, 'name' => 'IGV (Peru)', 'type' => 'percentage'],
        'TR' => ['rate' => 0.2000, 'name' => 'VAT (Turkey)', 'type' => 'percentage'],
        'IL' => ['rate' => 0.1700, 'name' => 'VAT (Israel)', 'type' => 'percentage'],
        'RU' => ['rate' => 0.2000, 'name' => 'VAT (Russia)', 'type' => 'percentage'],
        'UA' => ['rate' => 0.2000, 'name' => 'VAT (Ukraine)', 'type' => 'percentage'],
        'KZ' => ['rate' => 0.1200, 'name' => 'VAT (Kazakhstan)', 'type' => 'percentage'],
        'NG' => ['rate' => 0.0750, 'name' => 'VAT (Nigeria)', 'type' => 'percentage'],
        'KE' => ['rate' => 0.1600, 'name' => 'VAT (Kenya)', 'type' => 'percentage'],
        'EG' => ['rate' => 0.1400, 'name' => 'VAT (Egypt)', 'type' => 'percentage'],
        'MX' => ['rate' => 0.1600, 'name' => 'VAT (Mexico)', 'type' => 'percentage'],
        'BR' => ['rate' => 0.1700, 'name' => 'ICMS (Brazil)', 'type' => 'percentage'],
        'AR' => ['rate' => 0.2100, 'name' => 'VAT (Argentina)', 'type' => 'percentage'],
        'CL' => ['rate' => 0.1900, 'name' => 'VAT (Chile)', 'type' => 'percentage'],
        'CO' => ['rate' => 0.1900, 'name' => 'VAT (Colombia)', 'type' => 'percentage'],
        'PE' => ['rate' => 0.1800, 'name' => 'IGV (Peru)', 'type' => 'percentage'],
        'TR' => ['rate' => 0.2000, 'name' => 'VAT (Turkey)', 'type' => 'percentage'],
        'IL' => ['rate' => 0.1700, 'name' => 'VAT (Israel)', 'type' => 'percentage'],
        'RU' => ['rate' => 0.2000, 'name' => 'VAT (Russia)', 'type' => 'percentage'],
        'UA' => ['rate' => 0.2000, 'name' => 'VAT (Ukraine)', 'type' => 'percentage'],
        'KZ' => ['rate' => 0.1200, 'name' => 'VAT (Kazakhstan)', 'type' => 'percentage'],
        'NG' => ['rate' => 0.0750, 'name' => 'VAT (Nigeria)', 'type' => 'percentage'],
        'KE' => ['rate' => 0.1600, 'name' => 'VAT (Kenya)', 'type' => 'percentage'],
        'EG' => ['rate' => 0.1400, 'name' => 'VAT (Egypt)', 'type' => 'percentage'],
        'MX' => ['rate' => 0.1600, 'name' => 'VAT (Mexico)', 'type' => 'percentage'],
        'BR' => ['rate' => 0.1700, 'name' => 'VAT (Brazil)', 'type' => 'percentage'],
        'AR' => ['rate' => 0.2100, 'name' => 'VAT (Argentina)', 'type' => 'percentage'],
        'CL' => ['rate' => 0.1900, 'name' => 'VAT (Chile)', 'type' => 'percentage'],
        'CO' => ['rate' => 0.1900, 'name' => 'VAT (Colombia)', 'type' => 'percentage'],
        'PE' => ['rate' => 0.1800, 'name' => 'IGV (Peru)', 'type' => 'percentage'],
        'TR' => ['rate' => 0.2000, 'name' => 'VAT (Turkey)', 'type' => 'percentage'],
        'IL' => ['rate' => 0.1700, 'name' => 'VAT (Israel)', 'type' => 'percentage'],
        'RU' => ['rate' => 0.2000, 'name' => 'VAT (Russia)', 'type' => 'percentage'],
        'UA' => ['rate' => 0.2000, 'name' => 'VAT (Ukraine)', 'type' => 'percentage'],
        'KZ' => ['rate' => 0.1200, 'name' => 'VAT (Kazakhstan)', 'type' => 'percentage'],
    ];

    /**
     * Create a new tax rate.
     */
    public function createTaxRate(Tenant $tenant, array $data): TaxRate
    {
        return DB::transaction(function () use ($tenant, $data) {
            // If this is set as default, remove default from other rates
            if (($data['is_default'] ?? false) === true) {
                TaxRate::where('tenant_id', $tenant->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            return TaxRate::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'rate' => $data['rate'],
                'type' => $data['type'] ?? 'percentage',
                'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
                'region_code' => isset($data['region_code']) ? strtoupper($data['region_code']) : null,
                'is_default' => $data['is_default'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);
        });
    }

    /**
     * Update a tax rate.
     */
    public function updateTaxRate(TaxRate $taxRate, array $data): TaxRate
    {
        return DB::transaction(function () use ($taxRate, $data) {
            // If this is set as default, remove default from other rates
            if (($data['is_default'] ?? false) === true && ! $taxRate->is_default) {
                TaxRate::where('tenant_id', $taxRate->tenant_id)
                    ->where('id', '!=', $taxRate->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $taxRate->update([
                'name' => $data['name'] ?? $taxRate->name,
                'rate' => $data['rate'] ?? $taxRate->rate,
                'type' => $data['type'] ?? $taxRate->type,
                'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : $taxRate->country_code,
                'region_code' => isset($data['region_code']) ? strtoupper($data['region_code']) : $taxRate->region_code,
                'is_default' => $data['is_default'] ?? $taxRate->is_default,
                'is_active' => $data['is_active'] ?? $taxRate->is_active,
                'description' => $data['description'] ?? $taxRate->description,
                'metadata' => $data['metadata'] ?? $taxRate->metadata,
            ]);

            return $taxRate->fresh();
        });
    }

    /**
     * Delete a tax rate.
     */
    public function deleteTaxRate(TaxRate $taxRate): bool
    {
        return $taxRate->delete();
    }

    /**
     * Get tax rates for a tenant.
     */
    public function getTaxRates(Tenant $tenant, array $filters = []): Collection
    {
        $query = TaxRate::where('tenant_id', $tenant->id);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['country_code'])) {
            $query->where('country_code', strtoupper($filters['country_code']));
        }

        if (isset($filters['region_code'])) {
            $query->where('region_code', strtoupper($filters['region_code']));
        }

        return $query->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get applicable tax rate for a given country/region.
     */
    public function getApplicableTaxRate(Tenant $tenant, ?string $countryCode = null, ?string $regionCode = null): ?TaxRate
    {
        $countryCode = $countryCode ? strtoupper($countryCode) : null;
        $regionCode = $regionCode ? strtoupper($regionCode) : null;

        // Try to find exact match (country + region)
        if ($countryCode && $regionCode) {
            $taxRate = TaxRate::where('tenant_id', $tenant->id)
                ->where('country_code', $countryCode)
                ->where('region_code', $regionCode)
                ->where('is_active', true)
                ->first();

            if ($taxRate) {
                return $taxRate;
            }
        }

        // Try to find country-only match
        if ($countryCode) {
            $taxRate = TaxRate::where('tenant_id', $tenant->id)
                ->where('country_code', $countryCode)
                ->whereNull('region_code')
                ->where('is_active', true)
                ->first();

            if ($taxRate) {
                return $taxRate;
            }
        }

        // Fall back to default tax rate
        return TaxRate::where('tenant_id', $tenant->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Calculate tax for a given amount.
     */
    public function calculateTax(Tenant $tenant, float $amount, ?string $countryCode = null, ?string $regionCode = null): array
    {
        $taxRate = $this->getApplicableTaxRate($tenant, $countryCode, $regionCode);

        if (! $taxRate) {
            return [
                'tax_amount' => 0,
                'tax_rate' => 0,
                'tax_rate_id' => null,
                'total' => $amount,
            ];
        }

        $taxAmount = $taxRate->calculateTax($amount);

        return [
            'tax_amount' => round($taxAmount, 2),
            'tax_rate' => $taxRate->rate,
            'tax_rate_id' => $taxRate->id,
            'tax_rate_name' => $taxRate->name,
            'total' => round($amount + $taxAmount, 2),
        ];
    }

    /**
     * Validate tax rate data.
     */
    public function validateTaxRate(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Tax rate name is required.';
        }

        if (! isset($data['rate']) || ! is_numeric($data['rate'])) {
            $errors['rate'] = 'Tax rate must be a number.';
        } elseif ($data['rate'] < 0) {
            $errors['rate'] = 'Tax rate cannot be negative.';
        } elseif ($data['type'] === 'percentage' && $data['rate'] > 1) {
            $errors['rate'] = 'Percentage tax rate must be between 0 and 1.';
        }

        if (isset($data['type']) && ! in_array($data['type'], ['percentage', 'fixed'])) {
            $errors['type'] = 'Tax type must be either "percentage" or "fixed".';
        }

        if (isset($data['country_code']) && strlen($data['country_code']) !== 2) {
            $errors['country_code'] = 'Country code must be a 2-letter ISO code.';
        }

        return $errors;
    }

    /**
     * Get default tax rate for a country from predefined rates.
     */
    public function getDefaultCountryTaxRate(string $countryCode): ?array
    {
        return self::COUNTRY_TAX_RATES[strtoupper($countryCode)] ?? null;
    }

    /**
     * Create tax rate from country code.
     */
    public function createTaxRateFromCountry(Tenant $tenant, string $countryCode, ?string $regionCode = null): ?TaxRate
    {
        $defaultRate = $this->getDefaultCountryTaxRate($countryCode);

        if (! $defaultRate) {
            return null;
        }

        return $this->createTaxRate($tenant, [
            'name' => $defaultRate['name'],
            'rate' => $defaultRate['rate'],
            'type' => $defaultRate['type'],
            'country_code' => $countryCode,
            'region_code' => $regionCode,
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Get all supported countries with tax rates.
     */
    public function getSupportedCountries(): array
    {
        return self::COUNTRY_TAX_RATES;
    }
}
