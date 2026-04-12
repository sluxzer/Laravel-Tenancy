<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\InvoicePaid;
use App\Events\SubscriptionRenewed;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentProviders\PayPalService;
use App\Services\PaymentProviders\StripeService;
use App\Services\PaymentProviders\XenditService;
use Illuminate\Support\Facades\Log;

/**
 * Payment Service
 *
 * Handles payment processing across multiple payment providers.
 */
class PaymentService
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
     * Create a payment.
     */
    public function createPayment(Tenant $tenant, array $data): Payment
    {
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'invoice_id' => $data['invoice_id'] ?? null,
            'subscription_id' => $data['subscription_id'] ?? null,
            'amount' => $data['amount'],
            'currency_code' => $data['currency_code'],
            'payment_method' => $data['payment_method'],
            'payment_token' => $data['payment_token'] ?? null,
            'gateway' => $data['gateway'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'status' => 'pending',
            'metadata' => $data['metadata'] ?? [],
        ]);

        return $payment;
    }

    /**
     * Process a payment.
     */
    public function processPayment(Payment $payment): array
    {
        $result = ['success' => false, 'message' => 'Payment processing failed'];

        try {
            match ($payment->gateway) {
                'stripe' => $result = $this->processStripePayment($payment),
                'xendit' => $result = $this->processXenditPayment($payment),
                'paypal' => $result = $this->processPayPalPayment($payment),
                default => $result['message'] = 'Unsupported payment gateway',
            };
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata, [
                    'error' => $e->getMessage(),
                ]),
            ]);
        }

        if ($result['success']) {
            $this->handleSuccessfulPayment($payment, $result);
        }

        return $result;
    }

    /**
     * Process Stripe payment.
     */
    protected function processStripePayment(Payment $payment): array
    {
        if (! $payment->payment_token) {
            return ['success' => false, 'message' => 'Payment token required'];
        }

        $paymentIntent = $this->stripe->createPaymentIntent([
            'user' => $payment->user,
            'amount' => $payment->amount,
            'currency' => $payment->currency_code,
            'payment_method' => $payment->payment_token,
            'description' => "Payment for invoice {$payment->invoice_id}",
            'invoice_id' => $payment->invoice_id,
            'subscription_id' => $payment->subscription_id,
        ]);

        return [
            'success' => $paymentIntent->status === 'succeeded',
            'message' => $paymentIntent->status === 'succeeded' ? 'Payment successful' : 'Payment pending',
            'payment_intent_id' => $paymentIntent->id,
            'payment' => $paymentIntent,
        ];
    }

    /**
     * Process Xendit payment.
     */
    protected function processXenditPayment(Payment $payment): array
    {
        $invoice = $this->xendit->createInvoice([
            'external_id' => "payment_{$payment->id}",
            'amount' => (int) $payment->amount,
            'description' => "Payment for invoice {$payment->invoice_id}",
            'currency' => $payment->currency_code,
            'payer_email' => $payment->user->email,
            'should_send_email' => true,
            'user_id' => $payment->user_id,
            'metadata' => [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
            ],
        ]);

        $payment->update([
            'transaction_id' => $invoice['id'],
            'metadata' => array_merge($payment->metadata, [
                'invoice_url' => $invoice['invoice_url'],
                'external_id' => $invoice['external_id'],
            ]),
        ]);

        return [
            'success' => true,
            'message' => 'Invoice created',
            'payment_url' => $invoice['invoice_url'],
        ];
    }

    /**
     * Process PayPal payment.
     */
    protected function processPayPalPayment(Payment $payment): array
    {
        if (! $payment->payment_token) {
            return ['success' => false, 'message' => 'Payment token required'];
        }

        $order = $this->paypal->createOrder([
            'intent' => 'CAPTURE',
            'amount' => $payment->amount,
            'currency_code' => $payment->currency_code,
            'reference_id' => "payment_{$payment->id}",
            'description' => "Payment for invoice {$payment->invoice_id}",
            'return_url' => route('payment.success', $payment->id),
            'cancel_url' => route('payment.cancel', $payment->id),
        ]);

        $payment->update([
            'transaction_id' => $order['id'],
            'metadata' => array_merge($payment->metadata, [
                'order_id' => $order['id'],
            ]),
        ]);

        $approvalUrl = collect($order['links'])
            ->first(fn ($link) => $link['rel'] === 'approve')['href'] ?? null;

        return [
            'success' => true,
            'message' => 'Order created',
            'approval_url' => $approvalUrl,
            'order_id' => $order['id'],
        ];
    }

    /**
     * Handle successful payment.
     */
    protected function handleSuccessfulPayment(Payment $payment, array $result): void
    {
        $payment->update([
            'status' => 'paid',
            'transaction_id' => $result['transaction_id'] ?? $result['payment_intent_id'] ?? $result['order_id'] ?? $payment->transaction_id,
            'metadata' => array_merge($payment->metadata, $result),
        ]);

        // Update invoice if exists
        if ($payment->invoice) {
            $invoice = $payment->invoice;

            if ($invoice->status === 'unpaid') {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                event(new InvoicePaid($invoice));
            }
        }

        // Update subscription if exists
        if ($payment->subscription) {
            $subscription = $payment->subscription;

            if ($subscription->status === 'past_due') {
                $subscription->update(['status' => 'active']);
                event(new SubscriptionRenewed($subscription));
            }
        }
    }

    /**
     * Get user's payment methods.
     */
    public function getUserPaymentMethods(User $user): array
    {
        $methods = [];

        // Get Stripe payment methods if user has customer ID
        if ($user->stripe_customer_id) {
            $methods['stripe'] = $this->stripe->listPaymentMethods($user);
        }

        // Payment methods from other providers can be added here
        $methods['xendit'] = [];
        $methods['paypal'] = [];

        return $methods;
    }

    /**
     * Add payment method.
     */
    public function addPaymentMethod(User $user, string $paymentMethod, string $paymentToken, bool $isDefault = false): array
    {
        $result = match ($paymentMethod) {
            'stripe' => $this->addStripePaymentMethod($user, $paymentToken, $isDefault),
            default => throw new \Exception("Unsupported payment method: {$paymentMethod}"),
        };

        return $result;
    }

    /**
     * Add Stripe payment method.
     */
    protected function addStripePaymentMethod(User $user, string $paymentToken, bool $isDefault): array
    {
        $customer = $this->stripe->getOrCreateCustomer($user);

        $this->stripe->stripe->paymentMethods->attach($paymentToken, [
            'customer' => $customer->id,
        ]);

        if ($isDefault) {
            $this->stripe->setDefaultPaymentMethod($user, $paymentToken);
        }

        return [
            'id' => $paymentToken,
            'type' => 'card',
            'is_default' => $isDefault,
        ];
    }

    /**
     * Remove payment method.
     */
    public function removePaymentMethod(User $user, string $methodId): void
    {
        $this->stripe->detachPaymentMethod($methodId);
    }

    /**
     * Set default payment method.
     */
    public function setDefaultPaymentMethod(User $user, string $methodId): void
    {
        $this->stripe->setDefaultPaymentMethod($user, $methodId);
    }

    /**
     * Get payment service by gateway.
     */
    public function getGatewayService(string $gateway): object
    {
        return match ($gateway) {
            'stripe' => $this->stripe,
            'xendit' => $this->xendit,
            'paypal' => $this->paypal,
            default => throw new \Exception("Unsupported gateway: {$gateway}"),
        };
    }

    /**
     * Calculate processing fees.
     */
    public function calculateFees(float $amount, string $gateway): array
    {
        $fees = match ($gateway) {
            'stripe' => [
                'fixed' => 0.30,
                'percentage' => 2.9,
                'total' => 0.30 + ($amount * 0.029),
            ],
            'xendit' => [
                'fixed' => 4000, // IDR
                'percentage' => 0,
                'total' => 4000,
            ],
            'paypal' => [
                'fixed' => 0.30,
                'percentage' => 2.89,
                'total' => 0.30 + ($amount * 0.0289),
            ],
            default => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
        };

        return $fees;
    }

    /**
     * Create payment intent for subscription.
     */
    public function createSubscriptionPayment(Subscription $subscription, string $paymentMethod): array
    {
        return $this->stripe->createPaymentIntent([
            'user' => $subscription->user,
            'amount' => $subscription->plan->price_monthly,
            'currency' => $subscription->plan->currency_code,
            'payment_method' => $paymentMethod,
            'description' => "Subscription payment for {$subscription->plan->name}",
            'subscription_id' => $subscription->id,
        ]);
    }
}
