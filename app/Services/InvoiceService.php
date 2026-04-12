<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Invoice Service
 *
 * Handles invoice generation, payment tracking, and billing logic.
 */
class InvoiceService
{
    public function __construct(
        private TaxService $taxService,
    ) {}

    /**
     * Create an invoice.
     */
    public function createInvoice(Invoice $invoice, array $items = []): Invoice
    {
        $subtotal = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

        return DB::transaction(function () use ($invoice, $subtotal) {
            $invoice = Invoice::create([
                'tenant_id' => $invoice->tenant_id,
                'user_id' => $invoice->user_id,
                'subscription_id' => $invoice->subscription_id ?? null,
                'number' => $this->generateInvoiceNumber($invoice->tenant_id),
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'currency' => $invoice->currency,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(14)->toDateTimeString(),
                'metadata' => [],
            ]);

            // Create invoice items
            foreach ($items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'],
                    'total_price' => ($item['quantity'] ?? 1) * $item['unit_price'],
                ]);
            }

            return $invoice->load('items');
        });
    }

    /**
     * Generate unique invoice number.
     */
    private function generateInvoiceNumber(int $tenantId): string
    {
        return 'INV-'.date('Y').'-'.str_pad($tenantId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice, array $paymentData = []): Invoice
    {
        return DB::transaction(function () use ($invoice, $paymentData) {
            $transaction = Transaction::create([
                'tenant_id' => $invoice->tenant_id,
                'user_id' => $invoice->user_id,
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription_id ?? null,
                'type' => $paymentData['type'] ?? 'payment',
                'provider' => $paymentData['provider'] ?? 'manual',
                'provider_transaction_id' => $paymentData['provider_transaction_id'] ?? null,
                'amount' => $invoice->total_amount,
                'currency' => $invoice->currency,
                'status' => 'completed',
                'description' => $paymentData['description'] ?? null,
                'metadata' => $paymentData['metadata'] ?? [],
            ]);

            $invoice->update([
                'status' => 'paid',
                'paid_at' => Carbon::now()->toDateTimeString(),
                'payment_transaction_id' => $transaction->id,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Cancel invoice.
     */
    public function cancelInvoice(Invoice $invoice, ?string $reason = null): Invoice
    {
        $invoice->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now()->toDateTimeString(),
        ]);

        return $invoice->fresh();
    }

    /**
     * Apply tax to invoice.
     */
    public function applyTax(Invoice $invoice): Invoice
    {
        $tenant = $invoice->tenant;
        $taxResult = $taxService->calculateTax($tenant, $invoice->subtotal);

        $invoice->update([
            'tax_amount' => $taxResult['tax_amount'],
            'total_amount' => round($invoice->subtotal + $taxResult['tax_amount'], 2),
        ]);

        return $invoice->fresh();
    }

    /**
     * Get pending invoices for tenant.
     */
    public function getPendingInvoices(Tenant $tenant): Collection
    {
        return Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get overdue invoices for tenant.
     */
    public function getOverdueInvoices(Tenant $tenant): Collection
    {
        return Invoice::where('tenant_id', $tenant->id)
            ->where('status', 'overdue')
            ->where('due_date', '<', Carbon::now())
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get invoice by number.
     */
    public function getInvoiceByNumber(int $tenantId, string $number): ?Invoice
    {
        return Invoice::where('tenant_id', $tenantId)
            ->where('number', $number)
            ->first();
    }
}
