<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use App\Models\Tenant;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tax Rate Controller (Admin)
 *
 * Platform-level tax rate management.
 */
class TaxRateController extends Controller
{
    protected TaxService $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * Get all tax rates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaxRate::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('country_code')) {
            $query->where('country_code', strtoupper($request->input('country_code')));
        }

        $taxRates = $query->with('tenant')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $taxRates->items(),
            'pagination' => [
                'total' => $taxRates->total(),
                'per_page' => $taxRates->perPage(),
                'current_page' => $taxRates->currentPage(),
                'last_page' => $taxRates->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific tax rate.
     */
    public function show(string $id): JsonResponse
    {
        $taxRate = TaxRate::with('tenant')->findOrFail($id);

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
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
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

        $tenant = Tenant::findOrFail($validated['tenant_id']);
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
        $taxRate = TaxRate::findOrFail($id);

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
        $taxRate = TaxRate::findOrFail($id);
        $this->taxService->deleteTaxRate($taxRate);

        return response()->json([
            'success' => true,
            'message' => 'Tax rate deleted successfully',
        ]);
    }

    /**
     * Get supported countries with default tax rates.
     */
    public function supportedCountries(): JsonResponse
    {
        $countries = $this->taxService->getSupportedCountries();

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    /**
     * Calculate tax for a given amount.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'amount' => 'required|numeric|min:0',
            'country_code' => 'nullable|string|max:2',
            'region_code' => 'nullable|string|max:10',
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
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
}
