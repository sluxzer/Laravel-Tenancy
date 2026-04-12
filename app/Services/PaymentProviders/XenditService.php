<?php

declare(strict_types=1);

namespace App\Services\PaymentProviders;

use App\Models\Payment;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Xendit Payment Provider Service
 *
 * Handles Xendit payment operations including virtual accounts, invoices, and webhooks.
 */
class XenditService
{
    protected string $apiKey;

    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.xendit.api_key');
        $this->apiUrl = config('services.xendit.api_url', 'https://api.xendit.co');
    }

    /**
     * Make an authenticated request to Xendit API.
     */
    protected function request(string $method, string $endpoint, array $data = []): Response
    {
        return Http::withBasicAuth($this->apiKey, '')
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->$method("{$this->apiUrl}{$endpoint}", $data);
    }

    /**
     * Create a virtual account for payment.
     */
    public function createVirtualAccount(array $data): array
    {
        $response = $this->request('post', '/callback_virtual_accounts', [
            'external_id' => $data['external_id'],
            'bank_code' => $data['bank_code'],
            'name' => $data['name'],
            'expected_amount' => $data['expected_amount'],
            'is_closed' => $data['is_closed'] ?? false,
            'expiration_date' => $data['expiration_date'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => array_merge($data['metadata'] ?? [], [
                'tenant_id' => tenancy()->tenant?->id,
            ]),
        ]);

        if (! $response->successful()) {
            Log::error('Xendit virtual account creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create virtual account');
        }

        return $response->json();
    }

    /**
     * Get virtual account details.
     */
    public function getVirtualAccount(string $id): array
    {
        $response = $this->request('get', "/callback_virtual_accounts/{$id}");

        return $response->json();
    }

    /**
     * Create an invoice for payment.
     */
    public function createInvoice(array $data): array
    {
        $response = $this->request('post', '/v2/invoices', [
            'external_id' => $data['external_id'],
            'amount' => $data['amount'],
            'invoice_duration' => $data['invoice_duration'] ?? 86400, // 24 hours in seconds
            'description' => $data['description'] ?? null,
            'currency' => $data['currency'] ?? 'IDR',
            'customer' => $data['customer'] ?? null,
            'customer_notification_preference' => $data['customer_notification_preference'] ?? null,
            'payment_methods' => $data['payment_methods'] ?? null,
            'should_send_email' => $data['should_send_email'] ?? false,
            'success_redirect_url' => $data['success_redirect_url'] ?? null,
            'failure_redirect_url' => $data['failure_redirect_url'] ?? null,
            'fees' => $data['fees'] ?? [],
            'metadata' => array_merge($data['metadata'] ?? [], [
                'tenant_id' => tenancy()->tenant?->id,
                'user_id' => $data['user_id'] ?? null,
            ]),
        ]);

        if (! $response->successful()) {
            Log::error('Xendit invoice creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create invoice');
        }

        return $response->json();
    }

    /**
     * Get invoice details.
     */
    public function getInvoice(string $id): array
    {
        $response = $this->request('get', "/v2/invoices/{$id}");

        return $response->json();
    }

    /**
     * Expire an invoice.
     */
    public function expireInvoice(string $id): array
    {
        $response = $this->request('post', "/v2/invoices/{$id}/expire!");

        return $response->json();
    }

    /**
     * Create a recurring payment plan.
     */
    public function createPlan(array $data): array
    {
        $response = $this->request('post', '/recurring_payments/plans', [
            'name' => $data['name'],
            'amount' => $data['amount'],
            'interval' => $data['interval'], // DAY, WEEK, MONTH
            'interval_count' => $data['interval_count'] ?? 1,
            'should_send_email' => $data['should_send_email'] ?? true,
            'success_redirect_url' => $data['success_redirect_url'] ?? null,
            'failure_redirect_url' => $data['failure_redirect_url'] ?? null,
            'payment_methods' => $data['payment_methods'] ?? null,
            'metadata' => array_merge($data['metadata'] ?? [], [
                'tenant_id' => tenancy()->tenant?->id,
            ]),
        ]);

        if (! $response->successful()) {
            Log::error('Xendit plan creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create recurring payment plan');
        }

        return $response->json();
    }

    /**
     * Create a recurring payment for a customer.
     */
    public function createRecurringPayment(array $data): array
    {
        $response = $this->request('post', '/recurring_payments', [
            'external_id' => $data['external_id'],
            'payer_email' => $data['payer_email'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'interval' => $data['interval'],
            'interval_count' => $data['interval_count'] ?? 1,
            'should_send_email' => $data['should_send_email'] ?? true,
            'missed_payment_action' => $data['missed_payment_action'] ?? 'IGNORE',
            'recharge' => $data['recharge'] ?? false,
            'charge_immediately' => $data['charge_immediately'] ?? false,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'schedule' => $data['schedule'] ?? null,
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'metadata' => array_merge($data['metadata'] ?? [], [
                'tenant_id' => tenancy()->tenant?->id,
                'user_id' => $data['user_id'] ?? null,
            ]),
        ]);

        if (! $response->successful()) {
            Log::error('Xendit recurring payment creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create recurring payment');
        }

        return $response->json();
    }

    /**
     * Get recurring payment details.
     */
    public function getRecurringPayment(string $id): array
    {
        $response = $this->request('get', "/recurring_payments/{$id}");

        return $response->json();
    }

    /**
     * Pause a recurring payment.
     */
    public function pauseRecurringPayment(string $id): array
    {
        $response = $this->request('post', "/recurring_payments/{$id}/pause!");

        return $response->json();
    }

    /**
     * Resume a paused recurring payment.
     */
    public function resumeRecurringPayment(string $id): array
    {
        $response = $this->request('post', "/recurring_payments/{$id}/resume!");

        return $response->json();
    }

    /**
     * Cancel a recurring payment.
     */
    public function cancelRecurringPayment(string $id): array
    {
        $response = $this->request('post', "/recurring_payments/{$id}/cancel!");

        return $response->json();
    }

    /**
     * Refund a payment.
     */
    public function refund(string $paymentId, ?int $amount = null, ?string $reason = null): array
    {
        $data = [
            'payment_id' => $paymentId,
        ];

        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        if ($reason) {
            $data['reason'] = $reason;
        }

        $response = $this->request('post', '/refunds', $data);

        if (! $response->successful()) {
            Log::error('Xendit refund failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to process refund');
        }

        return $response->json();
    }

    /**
     * Get balance.
     */
    public function getBalance(string $accountType = 'CASH'): array
    {
        $response = $this->request('get', '/balance', [
            'account_type' => $accountType,
        ]);

        return $response->json();
    }

    /**
     * Create a payment method (e.g., card tokenization).
     */
    public function createPaymentMethod(array $data): array
    {
        $response = $this->request('post', '/payment_methods', [
            'type' => $data['type'], // CARD, BANK_ACCOUNT, EWALLET
            'reusability' => $data['reusability'] ?? 'MULTIPLE_USE',
            'card_details' => $data['card_details'] ?? null,
            'bank_account' => $data['bank_account'] ?? null,
            'ewallet' => $data['ewallet'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'metadata' => array_merge($data['metadata'] ?? [], [
                'tenant_id' => tenancy()->tenant?->id,
            ]),
        ]);

        if (! $response->successful()) {
            Log::error('Xendit payment method creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create payment method');
        }

        return $response->json();
    }

    /**
     * List payment channels (banks, e-wallets, etc).
     */
    public function listPaymentChannels(array $filters = []): array
    {
        $response = $this->request('get', '/payment_channels', $filters);

        return $response->json();
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(array $payload, string $token): bool
    {
        $expectedToken = hash_hmac(
            'sha256',
            json_encode($payload),
            $this->apiKey
        );

        return hash_equals($expectedToken, $token);
    }

    /**
     * Handle webhook event.
     */
    public function handleWebhook(array $payload): array
    {
        return [
            'event_type' => $payload['event_type'] ?? 'unknown',
            'event_id' => $payload['id'] ?? null,
            'external_id' => $payload['external_id'] ?? null,
            'status' => $payload['status'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'payment_id' => $payload['payment_id'] ?? null,
            'paid_at' => $payload['paid_at'] ?? null,
            'payment_method' => $payload['payment_method'] ?? null,
            'metadata' => $payload['metadata'] ?? [],
        ];
    }

    /**
     * Get supported banks.
     */
    public function getBanks(): array
    {
        $response = $this->request('get', '/available_virtual_account_banks');

        return $response->json();
    }

    /**
     * Calculate fees for a payment.
     */
    public function calculateFees(int $amount, string $paymentMethod): array
    {
        $response = $this->request('get', '/fees', [
            'amount' => $amount,
            'payment_method' => $paymentMethod,
        ]);

        return $response->json();
    }

    /**
     * Create a customer.
     */
    public function createCustomer(array $data): array
    {
        $response = $this->request('post', '/customers', [
            'reference_id' => $data['reference_id'],
            'given_names' => $data['given_names'],
            'email' => $data['email'],
            'mobile_number' => $data['mobile_number'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'addresses' => $data['addresses'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => array_merge($data['metadata'] ?? [], [
                'tenant_id' => tenancy()->tenant?->id,
            ]),
        ]);

        if (! $response->successful()) {
            Log::error('Xendit customer creation failed', [
                'error' => $response->json(),
            ]);

            throw new \Exception('Failed to create customer');
        }

        return $response->json();
    }

    /**
     * Get customer details.
     */
    public function getCustomer(string $id): array
    {
        $response = $this->request('get', "/customers/{$id}");

        return $response->json();
    }
}
