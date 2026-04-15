<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\Transaction;
use App\Services\RefundService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Refund Controller (Tenant)
 *
 * Tenant-level refund management.
 */
class RefundController
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Get all refunds for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Refund::where('tenant_id', $tenant->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $refunds = $query->with(['transaction', 'invoice', 'processedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $refunds->items(),
            'pagination' => [
                'total' => $refunds->total(),
                'per_page' => $refunds->perPage(),
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific refund.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $refund = Refund::where('tenant_id', $tenant->id)
            ->with(['transaction', 'invoice', 'processedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $refund,
        ]);
    }

    /**
     * Create a refund.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $transaction = Transaction::where('tenant_id', $tenant->id)
            ->findOrFail($validated['transaction_id']);

        $refund = $this->refundService->createRefund(
            $transaction,
            $validated['amount'],
            $validated['reason'] ?? null
        );

        if ($validated['notes'] ?? null) {
            $refund->update(['notes' => $validated['notes']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund created successfully',
            'data' => $refund->load(['transaction', 'invoice']),
        ], 201);
    }

    /**
     * Process a refund.
     */
    public function process(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $refund = Refund::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($refund->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Refund has already been processed',
            ], 400);
        }

        $result = $this->refundService->processRefund($refund);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully',
            'data' => $refund->load(['transaction', 'invoice']),
        ]);
    }

    /**
     * Cancel a refund.
     */
    public function cancel(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $refund = Refund::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($refund->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel processed refund',
            ], 400);
        }

        $result = $this->refundService->cancelRefund($refund);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund cancelled successfully',
            'data' => $refund->load(['transaction', 'invoice']),
        ]);
    }

    /**
     * Get refund summary.
     */
    public function summary(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();

        $refunds = Refund::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $summary = $refunds->groupBy('status')->map(fn ($group) => [
            'status' => $group->first()->status,
            'total_amount' => $group->sum('amount'),
            'count' => $group->count(),
        ])->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
            ],
        ]);
    }
}
