<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\InvoicePaid;
use App\Events\SubscriptionRenewed;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Transaction;
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
     * Create a payment transaction.
     */
    public function createPayment(Tenant $tenant, array $data): Transaction
    {
        $transaction = Transaction::create([
            'tenant_id' => $tenant->id,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'invoice_id' => $data['invoice_id'] ?? null,
            'subscription_id' => $data['subscription_id'] ?? null,
            'type' => 'payment',
            'provider' => $data['gateway'],
            'provider_transaction_id' => $data['transaction_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'pending',
            'description' => $data['description'] ?? 'Payment',
            'metadata' => $data['metadata'] ?? [],
        ]);

        return $transaction;
    }

    /**
     * Process a payment transaction.
     */
    public function processPayment(Transaction $transaction): array
    {
        $result = ['success' => false, 'message' => 'Payment processing failed'];

        try {
            $result = match ($transaction->provider) {
                'stripe' => $this->processStripePayment($transaction),
                'xendit' => $this->processXenditPayment($transaction),
                'paypal' => $this->processPayPalPayment($transaction),
                default => ['success' => false, 'message' => 'Unsupported payment gateway'],
            };
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            $transaction->update([
                'status' => 'failed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);
        }

        if ($result['success']) {
            $this->handleSuccessfulPayment($transaction, $result);
        }

        return $result;
    }

    /**
     * Process Stripe payment.
     */
    protected function processStripePayment(Transaction $transaction): array
    {
        $paymentToken = $transaction->metadata['payment_token'] ?? null;

        if (! $paymentToken) {
            return ['success' => false, 'message' => 'Payment token required'];
        }

        $paymentIntent = $this->stripe->createPaymentIntent([
            'user' => $transaction->user,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'payment_method' => $paymentToken,
            'description' => $transaction->description,
            'invoice_id' => $transaction->invoice_id,
            'subscription_id' => $transaction->subscription_id,
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
    protected function processXenditPayment(Transaction $transaction): array
    {
        $invoice = $this->xendit->createInvoice([
            'external_id' => "payment_{$transaction->id}",
            'amount' => (int) $transaction->amount,
            'description' => $transaction->description,
            'currency' => $transaction->currency,
            'payer_email' => $transaction->user->email,
            'should_send_email' => true,
            'user_id' => $transaction->user_id,
            'metadata' => [
                'transaction_id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
            ],
        ]);

        $transaction->update([
            'provider_transaction_id' => $invoice['id'],
            'metadata' => array_merge($transaction->metadata ?? [], [
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
    protected function processPayPalPayment(Transaction $transaction): array
    {
        $paymentToken = $transaction->metadata['payment_token'] ?? null;

        if (! $paymentToken) {
            return ['success' => false, 'message' => 'Payment token required'];
        }

        $order = $this->paypal->createOrder([
            'intent' => 'CAPTURE',
            'amount' => $transaction->amount,
            'currency_code' => $transaction->currency,
            'reference_id' => "payment_{$transaction->id}",
            'description' => $transaction->description,
            'return_url' => route('payment.success', $transaction->id),
            'cancel_url' => route('payment.cancel', $transaction->id),
        ]);

        $transaction->update([
            'provider_transaction_id' => $order['id'],
            'metadata' => array_merge($transaction->metadata ?? [], [
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
    protected function handleSuccessfulPayment(Transaction $transaction, array $result): void
    {
        $transaction->update([
            'status' => 'completed',
            'provider_transaction_id' => $result['transaction_id'] ?? $result['payment_intent_id'] ?? $result['order_id'] ?? $transaction->provider_transaction_id,
            'metadata' => array_merge($transaction->metadata ?? [], $result),
        ]);

        // Update invoice if exists
        if ($transaction->invoice) {
            $invoice = $transaction->invoice;

            if ($invoice->status === 'pending') {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                event(new InvoicePaid($invoice));
            }
        }

        // Update subscription if exists
        if ($transaction->subscription) {
            $subscription = $transaction->subscription;

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
