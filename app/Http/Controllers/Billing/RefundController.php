<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\RefundService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Refund Controller (Tenant)
 *
 * Tenant-level refund management.
 */
class RefundController extends Controller
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

        $refunds = $query->with(['payment', 'processedBy'])
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
            ->with(['payment', 'processedBy'])
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
            'payment_id' => 'required|exists:payments,id',
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $refund = $this->refundService->createRefund(
            $tenant,
            $validated['payment_id'],
            $validated['amount'],
            $validated['reason'] ?? null,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Refund created successfully',
            'data' => $refund->load(['payment', 'processedBy']),
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
            'data' => $result['refund'],
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

        $refund->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Refund cancelled successfully',
            'data' => $refund,
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

        $summary = Refund::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                status,
                SUM(amount) as total_amount,
                COUNT(*) as count
            ')
            ->groupBy('status')
            ->get();

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
