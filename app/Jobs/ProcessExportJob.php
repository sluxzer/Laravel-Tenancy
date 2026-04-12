<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExportJob;
use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Process Export Job
 *
 * Queue job for processing data export requests.
 */
class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    public function __construct(
        public ExportJob $job,
    ) {
        $this->job = $job;
    }

    /**
     * Execute the job.
     */
    public function handle(ExportService $exportService): void
    {
        $this->job->update(['status' => 'processing', 'progress' => 0]);

        $result = $exportService->processExportJob($this->job);

        $this->job->update([
            'status' => $result['success'] ? 'completed' : 'failed',
            'progress' => 100,
            'file_path' => $result['file_path'] ?? null,
            'file_size' => $result['file_size'] ?? null,
            'row_count' => $result['row_count'] ?? 0,
            'error_message' => $result['success'] ? null : $result['message'],
            'completed_at' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->job->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'progress' => 0,
            'completed_at' => now(),
        ]);
    }
}
