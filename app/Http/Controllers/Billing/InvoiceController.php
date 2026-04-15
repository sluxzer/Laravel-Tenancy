<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Invoice Controller (Tenant)
 *
 * Tenant-level invoice management.
 */
class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Get all invoices for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Invoice::where('tenant_id', $tenant->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('number', 'like', "%{$search}%");
        }

        $invoices = $query->with(['items', 'subscription'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $invoices->items(),
            'pagination' => [
                'total' => $invoices->total(),
                'per_page' => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific invoice.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->with(['items', 'subscription', 'transactions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Create a new invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'due_date' => 'required|date',
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'notes' => 'nullable|string',
        ]);

        $invoiceData = array_merge($validated, [
            'tenant_id' => $tenant->id,
        ]);

        $invoice = $this->invoiceService->createInvoice($invoiceData);

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully',
            'data' => $invoice->load(['items', 'subscription']),
        ], 201);
    }

    /**
     * Update an invoice.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,paid,overdue,cancelled',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'data' => $invoice->load(['items', 'subscription']),
        ]);
    }

    /**
     * Delete an invoice.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete paid invoice',
            ], 400);
        }

        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully',
        ]);
    }

    /**
     * Add item to invoice.
     */
    public function addItem(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'description' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $item = $this->invoiceService->addInvoiceItem($invoice, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Item added successfully',
            'data' => $item,
        ], 201);
    }

    /**
     * Remove item from invoice.
     */
    public function removeItem(string $id, string $itemId): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($id);
        $item = $invoice->items()->findOrFail($itemId);
        $item->delete();

        // Recalculate totals
        $totals = $this->invoiceService->calculateTotals($invoice);
        $invoice->update([
            'subtotal' => $totals['subtotal'],
            'total_amount' => $totals['total_amount'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item removed successfully',
        ]);
    }

    /**
     * Mark invoice as paid.
     */
    public function markPaid(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'provider' => 'required|string',
            'provider_transaction_id' => 'nullable|string',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $invoice = $this->invoiceService->markAsPaid($invoice, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid',
            'data' => $invoice->load(['items', 'subscription', 'transactions']),
        ]);
    }

    /**
     * Cancel invoice.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $invoice = $this->invoiceService->cancelInvoice($invoice, $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Invoice cancelled successfully',
            'data' => $invoice->load(['items', 'subscription']),
        ]);
    }
}
