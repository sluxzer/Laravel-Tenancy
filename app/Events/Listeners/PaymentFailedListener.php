<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\PaymentFailed;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Payment Failed Listener
 *
 * Sends notifications when a payment fails.
 */
class PaymentFailedListener implements ShouldQueue
{
    public int $tries = 3;

    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;
        $user = \App\Models\User::find($event->userId);
        $tenant = tenancy()->tenant;

        if (!$user || !$tenant) {
            return;
        }

        $notificationService = app(\App\Services\NotificationService::class);

        $notificationService->send($tenant($tenant, [
            'channel' => 'email',
            'title' => 'Payment Failed',
            'message' => "Payment of {$payment->amount}{$payment->currency_code} failed. Please try again.",
            'type' => 'payment_failed',
            'data' => [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'currency_code' => $payment->currency_code,
                'error_message' => $payment->errorMessage,
            ],
        ]);

        $this->error("Payment failed notification sent to user {$event->userId} for payment {$payment->id}");
    }
}
