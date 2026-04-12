<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Transaction Controller (Tenant)
 *
 * Tenant-level transaction management.
 */
class TransactionController extends Controller
{
    /**
     * Get all transactions for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Transaction::where('tenant_id', $tenant->id);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $transactions = $query->with(['invoice', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific transaction.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $transaction = Transaction::where('tenant_id', $tenant->id)
            ->with(['invoice', 'payment'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * Create a transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'invoice_id' => 'nullable|exists:invoices,id',
            'payment_id' => 'nullable|exists:payments,id',
            'type' => 'required|in:charge,refund,credit,debit',
            'amount' => 'required|numeric',
            'currency_code' => 'required|string|max:3',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,completed,failed',
            'metadata' => 'nullable|array',
        ]);

        $transaction = Transaction::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $validated['invoice_id'] ?? null,
            'payment_id' => $validated['payment_id'] ?? null,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'currency_code' => $validated['currency_code'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction->load(['invoice', 'payment']),
        ], 201);
    }

    /**
     * Update a transaction.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $transaction = Transaction::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,completed,failed',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $transaction->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully',
            'data' => $transaction->load(['invoice', 'payment']),
        ]);
    }

    /**
     * Get transaction summary.
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

        $summary = Transaction::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                type,
                status,
                SUM(CASE WHEN type = "charge" THEN amount ELSE 0 END) as total_charges,
                SUM(CASE WHEN type = "refund" THEN amount ELSE 0 END) as total_refunds,
                SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as total_credits,
                SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as total_debits,
                COUNT(*) as count
            ')
            ->groupBy('type', 'status')
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
