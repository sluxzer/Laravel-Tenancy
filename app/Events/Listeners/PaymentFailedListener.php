<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\PaymentFailed;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        $transaction = $event->transaction;
        $user = User::find($event->userId);
        $tenant = tenancy()->tenant;

        if (! $user || ! $tenant) {
            return;
        }

        $notificationService = app(NotificationService::class);

        $notificationService->send($tenant, [
            'channel' => 'email',
            'title' => 'Payment Failed',
            'message' => "Payment of {$transaction->currency}{$transaction->amount} failed. Please try again.",
            'type' => 'payment_failed',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'error_message' => $event->errorMessage,
            ],
        ]);

        $this->error("Payment failed notification sent to user {$event->userId} for transaction {$transaction->id}");
    }
}
