<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use Carbon\Carbon;
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
    public function createInvoice(array $data): Invoice
    {
        $items = $data['items'] ?? [];
        $subtotal = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

        return DB::transaction(function () use ($data, $subtotal, $items) {
            $invoice = Invoice::create([
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'],
                'subscription_id' => $data['subscription_id'] ?? null,
                'number' => $this->generateInvoiceNumber($data['tenant_id']),
                'subtotal' => $subtotal,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => $subtotal + ($data['tax_amount'] ?? 0) - ($data['discount_amount'] ?? 0),
                'currency' => $data['currency'] ?? 'USD',
                'status' => $data['status'] ?? 'pending',
                'due_date' => $data['due_date'] ?? Carbon::now()->addDays(14),
                'notes' => $data['notes'] ?? null,
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
        return 'INV-'.date('Y').'-'.str_pad((string) $tenantId, 6, '0', STR_PAD_LEFT)
            .'-'.str_pad((string) Invoice::where('tenant_id', $tenantId)->count() + 1, 4, '0', STR_PAD_LEFT);
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
                'description' => $paymentData['description'] ?? "Payment for invoice {$invoice->number}",
                'metadata' => $paymentData['metadata'] ?? [],
            ]);

            $invoice->update([
                'status' => 'paid',
                'paid_at' => Carbon::now(),
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
            'cancelled_at' => Carbon::now(),
            'notes' => $reason ? $invoice->notes.' - '.$reason : $invoice->notes,
        ]);

        return $invoice->fresh();
    }

    /**
     * Apply tax to invoice.
     */
    public function applyTax(Invoice $invoice): Invoice
    {
        $tenant = $invoice->tenant;
        $taxResult = $this->taxService->calculateTax($tenant, $invoice->subtotal);

        $invoice->update([
            'tax_amount' => $taxResult['tax_amount'],
            'total_amount' => round($invoice->subtotal + $taxResult['tax_amount'] - $invoice->discount_amount, 2),
        ]);

        return $invoice->fresh();
    }

    /**
     * Get pending invoices for tenant.
     */
    public function getPendingInvoices(int $tenantId): Collection
    {
        return Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get overdue invoices for tenant.
     */
    public function getOverdueInvoices(int $tenantId): Collection
    {
        return Invoice::where('tenant_id', $tenantId)
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

    /**
     * Add item to invoice.
     */
    public function addInvoiceItem(Invoice $invoice, array $data): InvoiceItem
    {
        return DB::transaction(function () use ($invoice, $data) {
            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $data['description'],
                'quantity' => $data['quantity'] ?? 1,
                'unit_price' => $data['unit_price'],
                'total_price' => ($data['quantity'] ?? 1) * $data['unit_price'],
            ]);

            // Update invoice totals
            $invoice->update([
                'subtotal' => $invoice->items()->sum('total_price'),
                'total_amount' => $invoice->subtotal + $invoice->tax_amount - $invoice->discount_amount,
            ]);

            return $item;
        });
    }

    /**
     * Calculate invoice totals from items.
     */
    public function calculateTotals(Invoice $invoice): array
    {
        $subtotal = $invoice->items()->sum('total_price');
        $taxRate = $this->taxService->getApplicableTaxRate($invoice->tenant);
        $taxAmount = $taxRate ? ($subtotal * $taxRate->rate) : 0;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => round($taxAmount, 2),
            'discount_amount' => $invoice->discount_amount,
            'total_amount' => round($subtotal + $taxAmount - $invoice->discount_amount, 2),
        ];
    }
}
