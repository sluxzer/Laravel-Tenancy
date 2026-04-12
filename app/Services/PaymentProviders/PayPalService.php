<?php

declare(strict_types=1);

namespace App\Services\PaymentProviders;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PayPal Payment Provider Service
 *
 * Handles PayPal payment operations including orders, subscriptions, and webhooks.
 */
class PayPalService
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $apiUrl;

    protected ?string $accessToken = null;

    public function __construct()
    {
        $isSandbox = config('services.paypal.sandbox', true);
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->apiUrl = $isSandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /**
     * Get or create access token.
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->apiUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            Log::error('PayPal access token request failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to get PayPal access token');
        }

        $this->accessToken = $response->json()['access_token'];

        return $this->accessToken;
    }

    /**
     * Make an authenticated request to PayPal API.
     */
    protected function request(string $method, string $endpoint, array $data = []): Response
    {
        $token = $this->getAccessToken();

        $http = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'PayPal-Request-Id' => Str::uuid()->toString(),
            ]);

        return match ($method) {
            'get' => $http->get("{$this->apiUrl}{$endpoint}", $data),
            'post' => $http->post("{$this->apiUrl}{$endpoint}", $data),
            'patch' => $http->patch("{$this->apiUrl}{$endpoint}", $data),
            'delete' => $http->delete("{$this->apiUrl}{$endpoint}", $data),
            default => throw new \Exception("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Create an order.
     */
    public function createOrder(array $data): array
    {
        $response = $this->request('post', '/v2/checkout/orders', [
            'intent' => $data['intent'] ?? 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $data['reference_id'] ?? null,
                    'description' => $data['description'] ?? null,
                    'custom_id' => $data['custom_id'] ?? null,
                    'soft_descriptor' => $data['soft_descriptor'] ?? null,
                    'amount' => [
                        'currency_code' => $data['currency_code'] ?? 'USD',
                        'value' => number_format($data['amount'], 2, '.', ''),
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => $data['brand_name'] ?? config('app.name'),
                'locale' => $data['locale'] ?? 'en-US',
                'landing_page' => $data['landing_page'] ?? 'BILLING',
                'shipping_preference' => $data['shipping_preference'] ?? 'NO_SHIPPING',
                'user_action' => $data['user_action'] ?? 'CONTINUE',
                'return_url' => $data['return_url'],
                'cancel_url' => $data['cancel_url'],
            ],
        ]);

        if (! $response->successful()) {
            Log::error('PayPal order creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create PayPal order');
        }

        return $response->json();
    }

    /**
     * Get order details.
     */
    public function getOrder(string $orderId): array
    {
        $response = $this->request('get', "/v2/checkout/orders/{$orderId}");

        return $response->json();
    }

    /**
     * Capture payment for an order.
     */
    public function captureOrder(string $orderId): array
    {
        $response = $this->request('post', "/v2/checkout/orders/{$orderId}/capture", [
            'note_to_payer' => null,
        ]);

        if (! $response->successful()) {
            Log::error('PayPal order capture failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to capture PayPal order');
        }

        return $response->json();
    }

    /**
     * Authorize payment for an order.
     */
    public function authorizeOrder(string $orderId): array
    {
        $response = $this->request('post', "/v2/checkout/orders/{$orderId}/authorize", [
            'note_to_payer' => null,
        ]);

        if (! $response->successful()) {
            Log::error('PayPal order authorization failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to authorize PayPal order');
        }

        return $response->json();
    }

    /**
     * Void an authorized payment.
     */
    public function voidAuthorization(string $authorizationId): array
    {
        $response = $this->request('post', "/v2/payments/authorizations/{$authorizationId}/void");

        if (! $response->successful()) {
            Log::error('PayPal authorization void failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to void PayPal authorization');
        }

        return $response->json();
    }

    /**
     * Capture an authorized payment.
     */
    public function captureAuthorization(string $authorizationId, ?float $amount = null): array
    {
        $data = [
            'final_capture' => true,
        ];

        if ($amount !== null) {
            $data['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => 'USD',
            ];
        }

        $response = $this->request('post', "/v2/payments/authorizations/{$authorizationId}/capture", $data);

        if (! $response->successful()) {
            Log::error('PayPal authorization capture failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to capture PayPal authorization');
        }

        return $response->json();
    }

    /**
     * Create a product.
     */
    public function createProduct(array $data): array
    {
        $response = $this->request('post', '/v1/catalogs/products', [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'SERVICE',
            'category' => $data['category'] ?? 'SOFTWARE',
            'image_url' => $data['image_url'] ?? null,
            'home_url' => $data['home_url'] ?? null,
        ]);

        if (! $response->successful()) {
            Log::error('PayPal product creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create PayPal product');
        }

        return $response->json();
    }

    /**
     * Create a plan for subscriptions.
     */
    public function createPlan(array $data): array
    {
        $response = $this->request('post', '/v1/billing/plans', [
            'product_id' => $data['product_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'ACTIVE',
            'billing_cycles' => $data['billing_cycles'],
            'payment_preferences' => [
                'auto_bill_outstanding' => $data['auto_bill_outstanding'] ?? true,
                'setup_fee' => $data['setup_fee'] ?? null,
                'setup_fee_failure_action' => $data['setup_fee_failure_action'] ?? 'CONTINUE',
                'payment_failure_threshold' => $data['payment_failure_threshold'] ?? 3,
            ],
        ]);

        if (! $response->successful()) {
            Log::error('PayPal plan creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create PayPal plan');
        }

        return $response->json();
    }

    /**
     * Create a subscription.
     */
    public function createSubscription(array $data): array
    {
        $response = $this->request('post', '/v1/billing/subscriptions', [
            'plan_id' => $data['plan_id'],
            'start_time' => $data['start_time'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'shipping_amount' => $data['shipping_amount'] ?? null,
            'subscriber' => $data['subscriber'] ?? [
                'name' => [
                    'given_name' => $data['subscriber_name'] ?? 'Subscriber',
                ],
                'email_address' => $data['subscriber_email'] ?? null,
            ],
            'auto_renewal' => $data['auto_renewal'] ?? true,
            'application_context' => [
                'brand_name' => $data['brand_name'] ?? config('app.name'),
                'locale' => $data['locale'] ?? 'en-US',
                'shipping_preference' => $data['shipping_preference'] ?? 'NO_SHIPPING',
                'user_action' => $data['user_action'] ?? 'SUBSCRIBE_NOW',
                'payment_method' => $data['payment_method'] ?? [
                    'payer_selected' => 'PAYPAL',
                    'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED',
                ],
                'return_url' => $data['return_url'],
                'cancel_url' => $data['cancel_url'],
            ],
        ]);

        if (! $response->successful()) {
            Log::error('PayPal subscription creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create PayPal subscription');
        }

        return $response->json();
    }

    /**
     * Get subscription details.
     */
    public function getSubscription(string $subscriptionId): array
    {
        $response = $this->request('get', "/v1/billing/subscriptions/{$subscriptionId}");

        return $response->json();
    }

    /**
     * Activate a subscription.
     */
    public function activateSubscription(string $subscriptionId, ?string $reason = null): array
    {
        $response = $this->request('post', "/v1/billing/subscriptions/{$subscriptionId}/activate", [
            'reason' => $reason,
        ]);

        if (! $response->successful()) {
            Log::error('PayPal subscription activation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to activate PayPal subscription');
        }

        return $response->json();
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(string $subscriptionId, ?string $reason = null): array
    {
        $response = $this->request('post', "/v1/billing/subscriptions/{$subscriptionId}/cancel", [
            'reason' => $reason,
        ]);

        if (! $response->successful()) {
            Log::error('PayPal subscription cancellation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to cancel PayPal subscription');
        }

        return $response->json();
    }

    /**
     * Suspend a subscription.
     */
    public function suspendSubscription(string $subscriptionId, ?string $reason = null): array
    {
        $response = $this->request('post', "/v1/billing/subscriptions/{$subscriptionId}/suspend", [
            'reason' => $reason,
        ]);

        if (! $response->successful()) {
            Log::error('PayPal subscription suspension failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to suspend PayPal subscription');
        }

        return $response->json();
    }

    /**
     * Revise a subscription (update plan or quantity).
     */
    public function reviseSubscription(string $subscriptionId, array $data): array
    {
        $response = $this->request('patch', "/v1/billing/subscriptions/{$subscriptionId}", $data);

        if (! $response->successful()) {
            Log::error('PayPal subscription revision failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to revise PayPal subscription');
        }

        return $response->json();
    }

    /**
     * Capture a payment for a subscription.
     */
    public function capturePayment(string $subscriptionId, array $data): array
    {
        $response = $this->request('post', "/v1/billing/subscriptions/{$subscriptionId}/capture", $data);

        if (! $response->successful()) {
            Log::error('PayPal payment capture failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to capture PayPal payment');
        }

        return $response->json();
    }

    /**
     * Refund a captured payment.
     */
    public function refundCapture(string $captureId, ?float $amount = null, ?string $currency = null): array
    {
        $data = [];

        if ($amount !== null) {
            $data['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => $currency ?? 'USD',
            ];
        }

        $response = $this->request('post', "/v2/payments/captures/{$captureId}/refund", $data);

        if (! $response->successful()) {
            Log::error('PayPal refund failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to process PayPal refund');
        }

        return $response->json();
    }

    /**
     * Show refund details.
     */
    public function getRefund(string $refundId): array
    {
        $response = $this->request('get', "/v2/payments/refunds/{$refundId}");

        return $response->json();
    }

    /**
     * Create a webhook.
     */
    public function createWebhook(array $data): array
    {
        $response = $this->request('post', '/v1/notifications/webhooks', [
            'url' => $data['url'],
            'event_types' => $data['event_types'],
        ]);

        if (! $response->successful()) {
            Log::error('PayPal webhook creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create PayPal webhook');
        }

        return $response->json();
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(array $payload, array $headers): bool
    {
        $certUrl = $headers['paypal-cert-url'] ?? $headers['PAYPAL-CERT-URL'] ?? null;
        $transmissionId = $headers['paypal-transmission-id'] ?? $headers['PAYPAL-TRANSMISSION-ID'] ?? null;
        $timestamp = $headers['paypal-transmission-time'] ?? $headers['PAYPAL-TRANSMISSION-TIME'] ?? null;
        $signature = $headers['paypal-auth-algo'] ?? $headers['PAYPAL-AUTH-ALGO'] ?? null;
        $certId = $headers['paypal-cert-id'] ?? $headers['PAYPAL-CERT-ID'] ?? null;
        $actualSignature = $headers['paypal-signature'] ?? $headers['PAYPAL-SIGNATURE'] ?? null;

        if (! $certUrl || ! $transmissionId || ! $timestamp || ! $signature || ! $certId || ! $actualSignature) {
            return false;
        }

        $response = Http::post($certUrl, [
            'auth_algo' => $signature,
            'cert_id' => $certId,
            'transmission_id' => $transmissionId,
            'transmission_sig' => $actualSignature,
            'transmission_time' => $timestamp,
            'webhook_id' => config('services.paypal.webhook_id'),
            'webhook_event' => $payload,
        ]);

        return $response->successful() && ($response->json()['verification_status'] ?? false) === 'SUCCESS';
    }

    /**
     * Handle webhook event.
     */
    public function handleWebhook(array $payload): array
    {
        return [
            'event_type' => $payload['event_type'] ?? null,
            'resource_type' => $payload['resource_type'] ?? null,
            'resource' => $payload['resource'] ?? [],
            'summary' => $payload['summary'] ?? null,
            'create_time' => $payload['create_time'] ?? null,
            'resource_version' => $payload['resource_version'] ?? null,
        ];
    }

    /**
     * Get transaction details.
     */
    public function getTransaction(string $transactionId): array
    {
        $response = $this->request('get', "/v2/payments/transactions/{$transactionId}");

        return $response->json();
    }

    /**
     * Get account balance.
     */
    public function getBalance(?string $currencyCode = null): array
    {
        $response = $this->request('get', '/v1/reporting/balances', [
            'currency_code' => $currencyCode,
        ]);

        return $response->json();
    }

    /**
     * Get list of products.
     */
    public function listProducts(array $params = []): array
    {
        $response = $this->request('get', '/v1/catalogs/products', $params);

        return $response->json();
    }

    /**
     * Get list of plans.
     */
    public function listPlans(array $params = []): array
    {
        $response = $this->request('get', '/v1/billing/plans', $params);

        return $response->json();
    }

    /**
     * Get list of subscriptions.
     */
    public function listSubscriptions(array $params = []): array
    {
        $response = $this->request('get', '/v1/billing/subscriptions', $params);

        return $response->json();
    }
}
