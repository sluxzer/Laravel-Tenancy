<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PaymentFailed;
use App\Models\Refund;
use App\Models\Transaction;
use App\Services\PaymentProviders\PayPalService;
use App\Services\PaymentProviders\StripeService;
use App\Services\PaymentProviders\XenditService;
use Illuminate\Support\Facades\Log;

/**
 * Refund Service
 *
 * Handles refund operations across multiple payment providers.
 */
class RefundService
{
    protected StripeService $stripe;

    protected XenditService $xendit;

    protected PayPalService $paypal;

    public function __construct(
        StripeService $stripe,
        XenditService $xendit,
        PayPalService $paypal,
    ) {
        $this->stripe = $stripe;
        $this->xendit = $xendit;
        $this->paypal = $paypal;
    }

    /**
     * Create a refund.
     */
    public function createRefund(Transaction $transaction, ?float $amount = null, ?string $reason = null): Refund
    {
        $refund = Refund::create([
            'tenant_id' => $transaction->tenant_id,
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'invoice_id' => $transaction->invoice_id,
            'amount' => $amount ?? $transaction->amount,
            'currency' => $transaction->currency,
            'reason' => $reason ?? 'requested_by_customer',
            'status' => 'pending',
            'notes' => null,
            'processed_by' => null,
            'processed_at' => null,
        ]);

        return $refund;
    }

    /**
     * Process a refund.
     */
    public function processRefund(Refund $refund): array
    {
        $transaction = $refund->transaction;

        if (! $transaction || $transaction->status !== 'completed') {
            return [
                'success' => false,
                'message' => 'Transaction must be in completed status to refund',
            ];
        }

        if ($refund->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Refund has already been processed',
            ];
        }

        if ($refund->amount > $transaction->amount) {
            return [
                'success' => false,
                'message' => 'Refund amount cannot exceed transaction amount',
            ];
        }

        $result = ['success' => false, 'message' => 'Refund processing failed'];

        try {
            $result = match ($transaction->provider) {
                'stripe' => $this->processStripeRefund($refund, $transaction),
                'xendit' => $this->processXenditRefund($refund, $transaction),
                'paypal' => $this->processPayPalRefund($refund, $transaction),
                default => ['success' => false, 'message' => 'Unsupported payment gateway'],
            };
        } catch (\Exception $e) {
            Log::error('Refund processing error', [
                'refund_id' => $refund->id,
                'error' => $e->getMessage(),
            ]);

            $refund->update([
                'status' => 'cancelled',
                'metadata' => array_merge($refund->metadata ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);
        }

        if ($result['success']) {
            $refund->update([
                'status' => 'processed',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
                'metadata' => array_merge($refund->metadata ?? [], $result),
            ]);

            event(new PaymentFailed($transaction, $refund->amount, $refund->reason));
        }

        return $result;
    }

    /**
     * Process Stripe refund.
     */
    protected function processStripeRefund(Refund $refund, Transaction $transaction): array
    {
        $amountInCents = (int) round($refund->amount * 100);

        $stripeRefund = $this->stripe->refundPayment(
            $transaction->provider_transaction_id,
            $refund->amount < $transaction->amount ? $amountInCents : null,
            $refund->reason,
        );

        return [
            'success' => $stripeRefund->status === 'succeeded',
            'message' => 'Refund processed successfully',
            'refund_id' => $stripeRefund->id,
            'amount' => $stripeRefund->amount / 100,
            'currency' => strtoupper($stripeRefund->currency),
            'status' => $stripeRefund->status,
            'reason' => $stripeRefund->reason,
        ];
    }

    /**
     * Process Xendit refund.
     */
    protected function processXenditRefund(Refund $refund, Transaction $transaction): array
    {
        $amountInSmallestUnit = (int) round($refund->amount);

        $xenditRefund = $this->xendit->refund(
            $transaction->provider_transaction_id,
            $refund->amount < $transaction->amount ? $amountInSmallestUnit : null,
            $refund->reason,
        );

        return [
            'success' => ($xenditRefund['status'] ?? '') === 'SUCCEEDED',
            'message' => 'Refund processed successfully',
            'refund_id' => $xenditRefund['id'] ?? null,
            'amount' => $xenditRefund['amount'] ?? $refund->amount,
            'currency' => $xenditRefund['currency'] ?? $refund->currency,
            'status' => $xenditRefund['status'] ?? null,
        ];
    }

    /**
     * Process PayPal refund.
     */
    protected function processPayPalRefund(Refund $refund, Transaction $transaction): array
    {
        $paypalRefund = $this->paypal->refundCapture(
            $transaction->provider_transaction_id,
            $refund->amount < $transaction->amount ? $refund->amount : null,
            $refund->currency,
        );

        return [
            'success' => ($paypalRefund['status'] ?? '') === 'COMPLETED',
            'message' => 'Refund processed successfully',
            'refund_id' => $paypalRefund['id'] ?? null,
            'amount' => (float) ($paypalRefund['amount']['value'] ?? $refund->amount),
            'currency' => $paypalRefund['amount']['currency_code'] ?? $refund->currency,
            'status' => $paypalRefund['status'] ?? null,
        ];
    }

    /**
     * Cancel a pending refund.
     */
    public function cancelRefund(Refund $refund): array
    {
        if ($refund->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Only pending refunds can be cancelled',
            ];
        }

        $refund->update([
            'status' => 'cancelled',
            'processed_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Refund cancelled successfully',
        ];
    }

    /**
     * Get refund summary for a tenant.
     */
    public function getRefundSummary(int $tenantId, array $filters = []): array
    {
        $query = Refund::where('tenant_id', $tenantId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $refunds = $query->get();

        $byStatus = $refunds->groupBy('status')->map(fn ($group) => $group->sum('amount'));

        $byProvider = $refunds->map(fn ($refund) => $refund->transaction->provider ?? 'unknown')
            ->groupBy(fn ($provider) => $provider)
            ->map(fn ($group) => $group->sum(fn ($refund) => $refund->amount));

        return [
            'total_refunds' => $refunds->count(),
            'total_amount' => $refunds->sum('amount'),
            'by_status' => $byStatus,
            'by_provider' => $byProvider,
            'recent' => $refunds->take(10),
        ];
    }

    /**
     * Get refundable amount for a transaction.
     */
    public function getRefundableAmount(Transaction $transaction): array
    {
        $refunded = Refund::where('transaction_id', $transaction->id)
            ->where('status', 'processed')
            ->sum('amount');

        return [
            'transaction_amount' => $transaction->amount,
            'already_refunded' => $refunded,
            'refundable_amount' => max(0, $transaction->amount - $refunded),
        ];
    }

    /**
     * Check if a refund is possible.
     */
    public function canRefund(Transaction $transaction, ?float $amount = null): array
    {
        if ($transaction->status !== 'completed') {
            return [
                'can_refund' => false,
                'reason' => 'Transaction is not in completed status',
            ];
        }

        $refundable = $this->getRefundableAmount($transaction);

        if ($amount && $amount > $refundable['refundable_amount']) {
            return [
                'can_refund' => false,
                'reason' => 'Refund amount exceeds refundable amount',
                'refundable_amount' => $refundable['refundable_amount'],
            ];
        }

        // Check time window (e.g., 180 days for Stripe)
        $refundWindow = config('services.refund.window_days', 180);
        $updatedAt = $transaction->updated_at ?? $transaction->created_at;

        if ($updatedAt && $updatedAt->diffInDays(now()) > $refundWindow) {
            return [
                'can_refund' => false,
                'reason' => "Refund window of {$refundWindow} days has expired",
            ];
        }

        return [
            'can_refund' => true,
            'refundable_amount' => $refundable['refundable_amount'],
        ];
    }

    /**
     * Calculate refund with fees.
     */
    public function calculateRefundWithFees(Transaction $transaction, float $amount): array
    {
        $refundable = $this->getRefundableAmount($transaction);

        if ($amount > $refundable['refundable_amount']) {
            throw new \Exception('Refund amount exceeds refundable amount');
        }

        // Check if fees are refundable based on policy
        $refundFees = config('services.refund.refund_fees', false);

        $fees = app(PaymentService::class)->calculateFees($transaction->amount, $transaction->provider);

        return [
            'refund_amount' => $amount,
            'fees' => $fees,
            'refund_fees' => $refundFees,
            'net_refund' => $refundFees ? $amount : $amount - $fees['total'],
        ];
    }
}
