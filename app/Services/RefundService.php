<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PaymentFailed;
use App\Models\Payment;
use App\Models\Refund;
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
    public function createRefund(Payment $payment, ?float $amount = null, ?string $reason = null): Refund
    {
        $refund = Refund::create([
            'tenant_id' => $payment->tenant_id,
            'user_id' => $payment->user_id,
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'subscription_id' => $payment->subscription_id,
            'amount' => $amount ?? $payment->amount,
            'currency_code' => $payment->currency_code,
            'reason' => $reason ?? 'requested_by_customer',
            'status' => 'pending',
            'gateway' => $payment->gateway,
            'metadata' => [
                'original_transaction_id' => $payment->transaction_id,
            ],
        ]);

        return $refund;
    }

    /**
     * Process a refund.
     */
    public function processRefund(Refund $refund): array
    {
        $payment = $refund->payment;

        if (! $payment || $payment->status !== 'paid') {
            return [
                'success' => false,
                'message' => 'Payment must be in paid status to refund',
            ];
        }

        if ($refund->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Refund has already been processed',
            ];
        }

        if ($refund->amount > $payment->amount) {
            return [
                'success' => false,
                'message' => 'Refund amount cannot exceed payment amount',
            ];
        }

        $result = ['success' => false, 'message' => 'Refund processing failed'];

        try {
            match ($payment->gateway) {
                'stripe' => $result = $this->processStripeRefund($refund, $payment),
                'xendit' => $result = $this->processXenditRefund($refund, $payment),
                'paypal' => $result = $this->processPayPalRefund($refund, $payment),
                default => $result['message'] = 'Unsupported payment gateway',
            };
        } catch (\Exception $e) {
            Log::error('Refund processing error', [
                'refund_id' => $refund->id,
                'error' => $e->getMessage(),
            ]);

            $refund->update([
                'status' => 'failed',
                'metadata' => array_merge($refund->metadata, [
                    'error' => $e->getMessage(),
                ]),
            ]);
        }

        if ($result['success']) {
            $refund->update([
                'status' => 'processed',
                'transaction_id' => $result['refund_id'] ?? $result['id'] ?? null,
                'processed_at' => now(),
                'metadata' => array_merge($refund->metadata, $result),
            ]);

            event(new PaymentFailed($payment, $refund->amount, $refund->reason));
        }

        return $result;
    }

    /**
     * Process Stripe refund.
     */
    protected function processStripeRefund(Refund $refund, Payment $payment): array
    {
        $amountInCents = (int) round($refund->amount * 100);

        $stripeRefund = $this->stripe->refundPayment(
            $payment->transaction_id,
            $refund->amount < $payment->amount ? $amountInCents : null,
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
    protected function processXenditRefund(Refund $refund, Payment $payment): array
    {
        $amountInSmallestUnit = (int) round($refund->amount);

        $xenditRefund = $this->xendit->refund(
            $payment->transaction_id,
            $refund->amount < $payment->amount ? $amountInSmallestUnit : null,
            $refund->reason,
        );

        return [
            'success' => ($xenditRefund['status'] ?? '') === 'SUCCEEDED',
            'message' => 'Refund processed successfully',
            'refund_id' => $xenditRefund['id'] ?? null,
            'amount' => $xenditRefund['amount'] ?? $refund->amount,
            'currency' => $xenditRefund['currency'] ?? $refund->currency_code,
            'status' => $xenditRefund['status'] ?? null,
        ];
    }

    /**
     * Process PayPal refund.
     */
    protected function processPayPalRefund(Refund $refund, Payment $payment): array
    {
        $paypalRefund = $this->paypal->refundCapture(
            $payment->transaction_id,
            $refund->amount < $payment->amount ? $refund->amount : null,
            $refund->currency_code,
        );

        return [
            'success' => ($paypalRefund['status'] ?? '') === 'COMPLETED',
            'message' => 'Refund processed successfully',
            'refund_id' => $paypalRefund['id'] ?? null,
            'amount' => (float) ($paypalRefund['amount']['value'] ?? $refund->amount),
            'currency' => $paypalRefund['amount']['currency_code'] ?? $refund->currency_code,
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
            'cancelled_at' => now(),
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

        $byGateway = $refunds->groupBy('gateway')->map(fn ($group) => $group->sum('amount'));

        return [
            'total_refunds' => $refunds->count(),
            'total_amount' => $refunds->sum('amount'),
            'by_status' => $byStatus,
            'by_gateway' => $byGateway,
            'recent' => $refunds->take(10),
        ];
    }

    /**
     * Get refundable amount for a payment.
     */
    public function getRefundableAmount(Payment $payment): array
    {
        $refunded = Refund::where('payment_id', $payment->id)
            ->where('status', 'processed')
            ->sum('amount');

        return [
            'payment_amount' => $payment->amount,
            'already_refunded' => $refunded,
            'refundable_amount' => max(0, $payment->amount - $refunded),
        ];
    }

    /**
     * Check if a refund is possible.
     */
    public function canRefund(Payment $payment, ?float $amount = null): array
    {
        if ($payment->status !== 'paid') {
            return [
                'can_refund' => false,
                'reason' => 'Payment is not in paid status',
            ];
        }

        $refundable = $this->getRefundableAmount($payment);

        if ($amount && $amount > $refundable['refundable_amount']) {
            return [
                'can_refund' => false,
                'reason' => 'Refund amount exceeds refundable amount',
                'refundable_amount' => $refundable['refundable_amount'],
            ];
        }

        // Check time window (e.g., 180 days for Stripe)
        $refundWindow = config('services.refund.window_days', 180);
        if ($payment->paid_at && $payment->paid_at->diffInDays(now()) > $refundWindow) {
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
    public function calculateRefundWithFees(Payment $payment, float $amount): array
    {
        $refundable = $this->getRefundableAmount($payment);

        if ($amount > $refundable['refundable_amount']) {
            throw new \Exception('Refund amount exceeds refundable amount');
        }

        // Check if fees are refundable based on policy
        $refundFees = config('services.refund.refund_fees', false);

        $fees = app(PaymentService::class)->calculateFees($payment->amount, $payment->gateway);

        return [
            'refund_amount' => $amount,
            'fees' => $fees,
            'refund_fees' => $refundFees,
            'net_refund' => $refundFees ? $amount : $amount - $fees['total'],
        ];
    }
}
