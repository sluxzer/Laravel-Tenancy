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
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        $invoices = $query->with(['items'])
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
            ->with(['items', 'subscription', 'payment'])
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
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'due_date' => 'required|date',
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|max:3',
            'notes' => 'nullable|string',
        ]);

        $invoice = $this->invoiceService->createInvoice($tenant, $validated);

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
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
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
     * Send invoice to customer.
     */
    public function send(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($id);

        $this->invoiceService->sendInvoice($invoice);

        return response()->json([
            'success' => true,
            'message' => 'Invoice sent successfully',
            'data' => $invoice,
        ]);
    }

    /**
     * Generate invoice PDF.
     */
    public function download(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($id);

        $pdfPath = $this->invoiceService->generatePdf($invoice);

        return response()->json([
            'success' => true,
            'data' => [
                'pdf_url' => $pdfPath,
            ],
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

        return response()->json([
            'success' => true,
            'message' => 'Item removed successfully',
        ]);
    }
}
