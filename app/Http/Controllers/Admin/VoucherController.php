<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Voucher Controller (Admin)
 *
 * Platform-level voucher management.
 */
class VoucherController extends Controller
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Get all vouchers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Voucher::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }

        $vouchers = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $vouchers->items(),
            'pagination' => [
                'total' => $vouchers->total(),
                'per_page' => $vouchers->perPage(),
                'current_page' => $vouchers->currentPage(),
                'last_page' => $vouchers->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific voucher.
     */
    public function show(string $id): JsonResponse
    {
        $voucher = Voucher::with(['appliedBy', 'plan'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $voucher,
        ]);
    }

    /**
     * Create a new voucher.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:vouchers',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_trial',
            'value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'current_uses' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
            'plan_id' => 'nullable|exists:plans,id',
            'trial_days' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        // Validate type-specific fields
        if ($validated['type'] === 'free_trial' && ! isset($validated['trial_days'])) {
            return response()->json([
                'success' => false,
                'message' => 'Trial days is required for free_trial type',
            ], 422);
        }

        $voucher = Voucher::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'value' => $validated['value'],
            'max_uses' => $validated['max_uses'] ?? null,
            'current_uses' => 0,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'plan_id' => $validated['plan_id'] ?? null,
            'trial_days' => $validated['trial_days'] ?? null,
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher created successfully',
            'data' => $voucher,
        ], 201);
    }

    /**
     * Update a voucher.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $voucher = Voucher::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'value' => 'sometimes|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
            'plan_id' => 'nullable|exists:plans,id',
            'metadata' => 'nullable|array',
        ]);

        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher updated successfully',
            'data' => $voucher,
        ]);
    }

    /**
     * Delete a voucher.
     */
    public function destroy(string $id): JsonResponse
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher deleted successfully',
        ]);
    }

    /**
     * Validate a voucher.
     */
    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        $result = $this->voucherService->validate(
            $validated['code'],
            $validated['user_id'] ?? null,
            $validated['plan_id'] ?? null
        );

        return response()->json([
            'success' => $result['valid'],
            'data' => $result,
        ]);
    }

    /**
     * Generate bulk vouchers.
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prefix' => 'required|string|max:10',
            'count' => 'required|integer|min:1|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_trial',
            'value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date',
            'plan_id' => 'nullable|exists:plans,id',
            'trial_days' => 'nullable|integer|min:0',
        ]);

        $vouchers = [];
        for ($i = 0; $i < $validated['count']; $i++) {
            $code = strtoupper($validated['prefix']).'-'.strtoupper(substr(md5(uniqid((string) $i, true)), 0, 8));

            $vouchers[] = Voucher::create([
                'code' => $code,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'value' => $validated['value'],
                'max_uses' => $validated['max_uses'] ?? null,
                'current_uses' => 0,
                'expires_at' => $validated['expires_at'] ?? null,
                'is_active' => true,
                'plan_id' => $validated['plan_id'] ?? null,
                'trial_days' => $validated['trial_days'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($vouchers).' vouchers generated successfully',
            'data' => $vouchers,
        ], 201);
    }
}
