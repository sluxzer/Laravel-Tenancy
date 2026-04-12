<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

/**
 * Subscription Created Event
 *
 * Broadcast when a new subscription is created.
 */
class SubscriptionCreated implements ShouldBroadcast
{
    public array $data = [];

    public function __construct(
        public int $subscriptionId,
        public string $planName,
        public float $amount,
        public int $userId,
    ) {
        $this->data = [
            'subscription_id' => $subscriptionId,
            'plan_name' => $planName,
            'amount' => $amount,
            'user_id' => $userId,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            'subscription_id' => $this->data['subscription_id'],
            'plan_name' => $this->data['plan_name'],
            'amount' => $this->data['amount'],
            'user_id' => $this->data['user_id'],
        ];
    }
}

/**
 * Subscription Renewed Event
 *
 * Broadcast when a subscription is renewed.
 */
class SubscriptionRenewed implements ShouldBroadcast
{
    public function __construct(
        public int $subscriptionId,
        public string $planName,
        public float $newAmount,
        public int $userId,
    ) {
        $this->data = [
            'subscription_id' => $subscriptionId,
            'plan_name' => $planName,
            'amount' => $newAmount,
            'user_id' => $userId,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            'subscription_id' => $this->data['subscription_id'],
            'plan_name' => $this->data['plan_name'],
            'amount' => $this->data['amount'],
            'user_id' => $this->data['user_id'],
        ];
    }
}

/**
 * Subscription Cancelled Event
 *
 * Broadcast when a subscription is cancelled.
 */
class SubscriptionCancelled implements ShouldBroadcast
{
    public function __construct(
        public int $subscriptionId,
        public string $reason,
        public int $userId,
    ) {
        $this->data = [
            'subscription_id' => $subscriptionId,
            'reason' => $reason,
            'user_id' => $userId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Invoice Created Event
 *
 * Broadcast when a new invoice is generated.
 */
class InvoiceCreated implements ShouldBroadcast
{
    public function __construct(
        public int $invoiceId,
        public float $amount,
        public string $currencyCode,
        public int $tenantId,
    ) {
        $this->data = [
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'tenant_id' => $tenantId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Invoice Paid Event
 *
 * Broadcast when an invoice is paid.
 */
class InvoicePaid implements ShouldBroadcast
{
    public function __construct(
        public int $invoiceId,
        public int $paymentId,
        public float $amount,
        public int $tenantId,
    ) {
        $this->data = [
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'tenant_id' => $tenantId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Payment Failed Event
 *
 * Broadcast when a payment fails.
 */
class PaymentFailed implements ShouldBroadcast
{
    public function __construct(
        public int $paymentId,
        public string $errorMessage,
        public int $tenantId,
    ) {
        $this->data = [
            'payment_id' => $paymentId,
            'error_message' => $errorMessage,
            'tenant_id' => $tenantId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Notification Sent Event
 *
 * Broadcast when a notification is sent.
 */
class NotificationSent implements ShouldBroadcast
{
    public function __construct(
        public int $notificationId,
        public string $type,
        public string $channel,
        public int $userId,
    ) {
        $this->data = [
            'notification_id' => $notificationId,
            'type' => $type,
            'channel' => $channel,
            'user_id' => $userId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Notification Read Event
 *
 * Broadcast when a notification is read.
 */
class NotificationRead implements ShouldBroadcast
{
    public function __construct(
        public int $notificationId,
        public int $userId,
    ) {
        $this->data = [
            'notification_id' => $notificationId,
            'user_id' => $userId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * User Logged In Event
 *
 * Broadcast when a user logs in.
 */
class UserLoggedIn implements ShouldBroadcast
{
    public function __construct(int $userId)
    {
        $this->data = ['user_id' => $userId];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * User Logged Out Event
 *
 * Broadcast when a user logs out.
 */
class UserLoggedOut implements ShouldBroadcast
{
    public function __construct(int $userId)
    {
        $this->data = ['user_id' => $userId];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Activity Logged Event
 *
 * Broadcast when an activity is logged.
 */
class ActivityLogged implements ShouldBroadcast
{
    public function __construct(
        public int $activityId,
        public string $type,
        public array $data,
        public int $tenantId,
    ) {
        $this->data = [
            'activity_id' => $activityId,
            'type' => $type,
            'data' => $data,
            'tenant_id' => $tenantId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Webhook Delivered Event
 *
 * Broadcast when a webhook is successfully delivered.
 */
class WebhookDelivered implements ShouldBroadcast
{
    public function __construct(
        public int $webhookId,
        public string $eventType,
        public int $tenantId,
    ) {
        $this->data = [
            'webhook_id' => $webhookId,
            'event_type' => $eventType,
            'tenant_id' => $tenantId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Webhook Failed Event
 *
 * Broadcast when a webhook delivery fails.
 */
class WebhookFailed implements ShouldBroadcast
{
    public function __construct(
        public int $webhookId,
        public string $errorMessage,
        public int $tenantId,
    ) {
        $this->data = [
            'webhook_id' => $webhookId,
            'error_message' => $errorMessage,
            'tenant_id' => $tenantId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Tenant Created Event
 *
 * Broadcast when a new tenant is created.
 */
class TenantCreated implements ShouldBroadcast
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public int $ownerId,
    ) {
        $this->data = [
            'tenant_id' => $tenantId,
            'name' => $name,
            'owner_id' => $ownerId,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Tenant Suspended Event
 *
 * Broadcast when a tenant is suspended.
 */
class TenantSuspended implements ShouldBroadcast
{
    public function __construct(
        public int $tenantId,
        public string $reason,
    ) {
        $this->data = [
            'tenant_id' => $tenantId,
            'reason' => $reason,
        ];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * Tenant Activated Event
 *
 * Broadcast when a tenant is activated.
 */
class TenantActivated implements ShouldBroadcast
{
    public function __construct(int $tenantId)
    {
        $this->data = ['tenant_id' => $tenantId];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}

/**
 * User Registered Event
 *
 * Broadcast when a new user registers (global).
 */
class UserRegistered implements ShouldBroadcast
{
    public function __construct(int $userId)
    {
        $this->data = ['user_id' => $userId];
    }

    public function broadcastOn(): array
    {
        return $this->data;
    }
}
