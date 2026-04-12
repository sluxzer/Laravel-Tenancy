<?php

declare(strict_types=1);

namespace App\Services\PaymentProviders;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Stripe\Customer as StripeCustomer;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook;

/**
 * Stripe Payment Provider Service
 *
 * Handles Stripe payment operations including subscriptions, payments, and webhooks.
 */
class StripeService
{
    protected StripeClient $stripe;

    protected string $apiKey;

    protected string $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret_key');
        $this->webhookSecret = config('services.stripe.webhook_secret');
        $this->stripe = new StripeClient($this->apiKey);
    }

    /**
     * Create a Stripe customer.
     */
    public function createCustomer(array $data): StripeCustomer
    {
        return $this->stripe->customers->create([
            'email' => $data['email'],
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * Get or create a Stripe customer for a user.
     */
    public function getOrCreateCustomer(object $user): StripeCustomer
    {
        if ($user->stripe_customer_id) {
            try {
                return $this->stripe->customers->retrieve($user->stripe_customer_id);
            } catch (\Exception $e) {
                Log::warning('Stripe customer not found, creating new', [
                    'user_id' => $user->id,
                    'stripe_customer_id' => $user->stripe_customer_id,
                ]);
            }
        }

        $customer = $this->createCustomer([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
                'tenant_id' => tenancy()->tenant?->id,
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Create a payment intent for a one-time payment.
     */
    public function createPaymentIntent(array $data): PaymentIntent
    {
        $customer = $this->getOrCreateCustomer($data['user']);

        $params = [
            'amount' => (int) round($data['amount'] * 100), // Convert to cents
            'currency' => strtolower($data['currency'] ?? 'usd'),
            'customer' => $customer->id,
            'payment_method_types' => $data['payment_method_types'] ?? ['card'],
            'metadata' => [
                'tenant_id' => tenancy()->tenant?->id,
                'user_id' => $data['user']->id,
                'invoice_id' => $data['invoice_id'] ?? null,
                'subscription_id' => $data['subscription_id'] ?? null,
            ],
        ];

        if (! empty($data['payment_method'])) {
            $params['payment_method'] = $data['payment_method'];
            $params['confirm'] = true;
        }

        if (! empty($data['description'])) {
            $params['description'] = $data['description'];
        }

        return $this->stripe->paymentIntents->create($params);
    }

    /**
     * Create a Stripe subscription.
     */
    public function createSubscription(Subscription $subscription, string $paymentMethodId): StripeSubscription
    {
        $customer = $this->getOrCreateCustomer($subscription->user);
        $tenant = tenancy()->tenant;

        // Attach payment method to customer
        $this->stripe->paymentMethods->attach($paymentMethodId, [
            'customer' => $customer->id,
        ]);

        // Set as default payment method
        $this->stripe->customers->update($customer->id, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);

        // Create price if needed
        $priceId = $this->getOrCreatePrice($subscription->plan, $subscription->billing_cycle);

        // Create subscription
        $stripeSubscription = $this->stripe->subscriptions->create([
            'customer' => $customer->id,
            'items' => [['price' => $priceId]],
            'metadata' => [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
            ],
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        return $stripeSubscription;
    }

    /**
     * Get or create a Stripe price for a plan.
     */
    public function getOrCreatePrice(object $plan, string $billingCycle): string
    {
        $amountKey = $billingCycle === 'yearly' ? 'price_yearly' : 'price_monthly';
        $amount = (int) round($plan->$amountKey * 100);
        $currency = strtolower($plan->currency_code ?? 'usd');
        $interval = $billingCycle === 'yearly' ? 'year' : ($billingCycle === 'quarterly' ? 'month' : 'month');

        // Search for existing price
        $prices = $this->stripe->prices->all([
            'lookup_keys' => ["plan_{$plan->id}_{$billingCycle}"],
            'active' => true,
        ]);

        if ($prices->count() > 0) {
            return $prices->first()->id;
        }

        // Create new price
        $product = $this->getOrCreateProduct($plan);

        $price = $this->stripe->prices->create([
            'unit_amount' => $amount,
            'currency' => $currency,
            'recurring' => ['interval' => $interval],
            'product' => $product->id,
            'lookup_key' => "plan_{$plan->id}_{$billingCycle}",
            'metadata' => [
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
            ],
        ]);

        return $price->id;
    }

    /**
     * Get or create a Stripe product for a plan.
     */
    protected function getOrCreateProduct(object $plan): object
    {
        $tenant = tenancy()->tenant;

        $products = $this->stripe->products->all([
            'lookup_keys' => ["plan_{$plan->id}"],
            'active' => true,
        ]);

        if ($products->count() > 0) {
            return $products->first();
        }

        return $this->stripe->products->create([
            'name' => $plan->name,
            'description' => $plan->description ?? null,
            'lookup_key' => "plan_{$plan->id}",
            'metadata' => [
                'plan_id' => $plan->id,
                'tenant_id' => $tenant->id,
            ],
        ]);
    }

    /**
     * Cancel a Stripe subscription.
     */
    public function cancelSubscription(string $stripeSubscriptionId, bool $atPeriodEnd = true): StripeSubscription
    {
        return $this->stripe->subscriptions->update($stripeSubscriptionId, [
            'cancel_at_period_end' => $atPeriodEnd,
        ]);
    }

    /**
     * Resume a cancelled Stripe subscription.
     */
    public function resumeSubscription(string $stripeSubscriptionId): StripeSubscription
    {
        return $this->stripe->subscriptions->update($stripeSubscriptionId, [
            'cancel_at_period_end' => false,
        ]);
    }

    /**
     * Update Stripe subscription (upgrade/downgrade).
     */
    public function updateSubscription(string $stripeSubscriptionId, string $newPriceId): StripeSubscription
    {
        return $this->stripe->subscriptions->update($stripeSubscriptionId, [
            'items' => [
                ['id' => $this->getSubscriptionItemId($stripeSubscriptionId), 'price' => $newPriceId],
            ],
            'proration_behavior' => 'create_prorations',
        ]);
    }

    /**
     * Get subscription item ID from Stripe subscription.
     */
    protected function getSubscriptionItemId(string $stripeSubscriptionId): string
    {
        $subscription = $this->stripe->subscriptions->retrieve($stripeSubscriptionId);

        return $subscription->items->data[0]->id;
    }

    /**
     * Create a setup intent for saving payment methods.
     */
    public function createSetupIntent(object $user): object
    {
        $customer = $this->getOrCreateCustomer($user);

        return $this->stripe->setupIntents->create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
        ]);
    }

    /**
     * List customer payment methods.
     */
    public function listPaymentMethods(object $user): array
    {
        if (! $user->stripe_customer_id) {
            return [];
        }

        $paymentMethods = $this->stripe->paymentMethods->all([
            'customer' => $user->stripe_customer_id,
            'type' => 'card',
        ]);

        $defaultPaymentMethod = null;

        try {
            $customer = $this->stripe->customers->retrieve($user->stripe_customer_id);
            $defaultPaymentMethod = $customer->invoice_settings->default_payment_method ?? null;
        } catch (\Exception $e) {
            // Customer not found
        }

        return collect($paymentMethods->data)->map(function ($method) use ($defaultPaymentMethod) {
            return [
                'id' => $method->id,
                'type' => $method->type,
                'brand' => $method->card->brand ?? null,
                'last4' => $method->card->last4 ?? null,
                'exp_month' => $method->card->exp_month ?? null,
                'exp_year' => $method->card->exp_year ?? null,
                'is_default' => $method->id === $defaultPaymentMethod,
            ];
        })->toArray();
    }

    /**
     * Detach a payment method.
     */
    public function detachPaymentMethod(string $paymentMethodId): void
    {
        $this->stripe->paymentMethods->detach($paymentMethodId);
    }

    /**
     * Set default payment method.
     */
    public function setDefaultPaymentMethod(object $user, string $paymentMethodId): void
    {
        if (! $user->stripe_customer_id) {
            return;
        }

        $this->stripe->customers->update($user->stripe_customer_id, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): object
    {
        return Webhook::constructEvent(
            $payload,
            $signature,
            $this->webhookSecret
        );
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(string $paymentIntentId, ?int $amount = null, ?string $reason = null): object
    {
        $params = ['payment_intent' => $paymentIntentId];

        if ($amount !== null) {
            $params['amount'] = $amount;
        }

        if ($reason) {
            $params['reason'] = $reason; // 'duplicate', 'fraudulent', 'requested_by_customer'
        }

        return $this->stripe->refunds->create($params);
    }

    /**
     * Get invoice details.
     */
    public function getInvoice(string $invoiceId): object
    {
        return $this->stripe->invoices->retrieve($invoiceId);
    }

    /**
     * Create an invoice.
     */
    public function createInvoice(string $customerId): object
    {
        return $this->stripe->invoices->create(['customer' => $customerId]);
    }

    /**
     * Pay an invoice.
     */
    public function payInvoice(string $invoiceId): object
    {
        return $this->stripe->invoices->pay($invoiceId);
    }

    /**
     * List subscriptions for a customer.
     */
    public function listCustomerSubscriptions(string $customerId): array
    {
        $subscriptions = $this->stripe->subscriptions->all(['customer' => $customerId]);

        return collect($subscriptions->data)->map(function ($sub) {
            return [
                'id' => $sub->id,
                'status' => $sub->status,
                'current_period_start' => $sub->current_period_start,
                'current_period_end' => $sub->current_period_end,
                'cancel_at_period_end' => $sub->cancel_at_period_end,
                'items' => $sub->items->data,
            ];
        })->toArray();
    }
}
