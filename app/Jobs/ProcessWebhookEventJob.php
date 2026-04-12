<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Process Webhook Event Job
 *
 * Queue job for delivering webhook events to configured endpoints.
 */
class ProcessWebhookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 60;

    public function __construct(
        public WebhookEvent $event,
    ) {
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        $this->event->update(['status' => 'delivering']);

        $result = $webhookService->deliverEvent($this->event);

        $this->event->update([
            'status' => $result['success'] ? 'delivered' : 'failed',
            'response_code' => $result['response_code'] ?? null,
            'error_message' => $result['success'] ? null : $result['message'],
            'delivered_at' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->event->update([
            'status' => 'failed',
            'response_code' => null,
            'error_message' => $exception->getMessage(),
            'delivered_at' => now(),
        ]);
    }
}
