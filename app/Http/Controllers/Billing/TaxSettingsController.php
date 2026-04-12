<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use App\Models\TenantTaxSetting;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tax Settings Controller (Tenant)
 *
 * Tenant-level tax settings management.
 */
class TaxSettingsController extends Controller
{
    protected TaxService $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * Get tenant tax settings.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = TaxRate::where('tenant_id', $tenant->id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $taxRates = $query->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        $settings = TenantTaxSetting::where('tenant_id', $tenant->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'tax_rates' => $taxRates,
                'settings' => $settings,
            ],
        ]);
    }

    /**
     * Get a specific tax rate.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $taxRate = TaxRate::where('tenant_id', $tenant->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $taxRate,
        ]);
    }

    /**
     * Create a new tax rate.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'type' => 'sometimes|in:percentage,fixed',
            'country_code' => 'nullable|string|max:2',
            'region_code' => 'nullable|string|max:10',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        // Validate tax rate data
        $errors = $this->taxService->validateTaxRate($validated);
        if (! empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }

        $taxRate = $this->taxService->createTaxRate($tenant, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Tax rate created successfully',
            'data' => $taxRate,
        ], 201);
    }

    /**
     * Update a tax rate.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $taxRate = TaxRate::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'rate' => 'sometimes|numeric|min:0',
            'type' => 'sometimes|in:percentage,fixed',
            'country_code' => 'nullable|string|max:2',
            'region_code' => 'nullable|string|max:10',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $taxRate = $this->taxService->updateTaxRate($taxRate, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Tax rate updated successfully',
            'data' => $taxRate,
        ]);
    }

    /**
     * Delete a tax rate.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $taxRate = TaxRate::where('tenant_id', $tenant->id)->findOrFail($id);
        $this->taxService->deleteTaxRate($taxRate);

        return response()->json([
            'success' => true,
            'message' => 'Tax rate deleted successfully',
        ]);
    }

    /**
     * Calculate tax for a given amount.
     */
    public function calculate(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'country_code' => 'nullable|string|max:2',
            'region_code' => 'nullable|string|max:10',
        ]);

        $result = $this->taxService->calculateTax(
            $tenant,
            (float) $validated['amount'],
            $validated['country_code'] ?? null,
            $validated['region_code'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Update tenant tax settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'tax_inclusive' => 'boolean',
            'calculate_tax' => 'boolean',
            'default_country_code' => 'nullable|string|max:2',
            'tax_number' => 'nullable|string|max:255',
            'tax_registration_country' => 'nullable|string|max:2',
        ]);

        $settings = TenantTaxSetting::updateOrCreate(
            ['tenant_id' => $tenant->id],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Tax settings updated successfully',
            'data' => $settings,
        ]);
    }

    /**
     * Create tax rate from country code.
     */
    public function createFromCountry(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'country_code' => 'required|string|max:2',
            'region_code' => 'nullable|string|max:10',
        ]);

        $taxRate = $this->taxService->createTaxRateFromCountry(
            $tenant,
            $validated['country_code'],
            $validated['region_code'] ?? null
        );

        if (! $taxRate) {
            return response()->json([
                'success' => false,
                'message' => 'Default tax rate not found for this country',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tax rate created successfully',
            'data' => $taxRate,
        ], 201);
    }
}
