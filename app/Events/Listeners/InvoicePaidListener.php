<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Invoice Paid Listener
 *
 * Sends notifications when an invoice is paid.
 */
class InvoicePaidListener implements ShouldQueue
{
    public int $tries = 3;

    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $user = User::find($event->userId);
        $tenant = tenancy()->tenant;

        if (! $user || ! $tenant) {
            return;
        }

        $notificationService = app(NotificationService::class);

        $notificationService->send($tenant, [
            'channel' => 'email',
            'title' => 'Payment Received',
            'message' => "Payment of {$invoice->currency}{$invoice->total_amount} has been received for invoice #{$invoice->id}.",
            'type' => 'invoice_paid',
            'data' => [
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount,
                'currency' => $invoice->currency,
            ],
        ]);

        $this->info("Invoice paid notification sent to user {$event->userId} for invoice {$invoice->id}");
    }
}
