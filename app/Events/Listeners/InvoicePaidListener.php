<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

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
        $user = \App\Models\User::find($event->userId);
        $tenant = tenancy()->tenant;

        if (!$user || !$tenant) {
            return;
        }

        $notificationService = app(\App\Services\NotificationService::class);

        $notificationService->send($tenant($tenant, [
            'channel' => 'email',
            'title' => "Payment Received",
            'message' => "Payment of {$invoice->currency_code}{$invoice->amount} has been received for invoice #{$invoice->id}.",
            'type' => 'invoice_paid',
            'data' => [
                'invoice_id' => $invoice->id,
                'amount' => $invoice->amount,
                'currency_code' => $invoice->currency_code,
            ],
        ]);

        $this->info("Invoice paid notification sent to user {$event->userId} for invoice {$invoice->id}");
    }
}
