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
class TransactionController
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

        $transactions = $query->with(['invoice', 'subscription'])
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
            ->with(['invoice', 'subscription'])
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
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'type' => 'required|in:charge,refund,credit,debit,payment',
            'provider' => 'required|string',
            'provider_transaction_id' => 'nullable|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string|max:3',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,completed,failed',
            'metadata' => 'nullable|array',
        ]);

        $transaction = Transaction::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'invoice_id' => $validated['invoice_id'] ?? null,
            'subscription_id' => $validated['subscription_id'] ?? null,
            'type' => $validated['type'],
            'provider' => $validated['provider'],
            'provider_transaction_id' => $validated['provider_transaction_id'] ?? null,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction->load(['invoice', 'subscription']),
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
            'data' => $transaction->load(['invoice', 'subscription']),
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

        $transactions = Transaction::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $summary = $transactions->groupBy(fn ($t) => $t->type)
            ->map(fn ($group, $type) => [
                'type' => $type,
                'total_amount' => $group->sum('amount'),
                'count' => $group->count(),
                'by_status' => $group->groupBy('status')->map(fn ($statusGroup) => [
                    'status' => $statusGroup->first()->status,
                    'total_amount' => $statusGroup->sum('amount'),
                    'count' => $statusGroup->count(),
                ])->values()->all(),
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
