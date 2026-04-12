<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportJob as ImportJobModel;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Process Import Job
 *
 * Queue job for processing data import requests.
 */
class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    public function __construct(
        public string $jobId,
    ) {
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(ImportService $importService): void
    {
        $job = ImportJobModel::where('id', $this->jobId)->first();
        if (! $job) {
            return;
        }

        $job->update(['status' => 'processing', 'progress' => 0]);

        $result = $importService->processImportJob($job);

        $job->update([
            'status' => $result['success'] ? 'completed' : 'failed',
            'progress' => 100,
            'processed_rows' => $result['processed_rows'] ?? 0,
            'successful_rows' => $result['successful_rows'] ?? 0,
            'failed_rows' => $result['failed_rows'] ?? 0,
            'error_message' => $result['success'] ? null : $result['message'],
            'completed_at' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        ImportJobModel::where('id', $this->jobId)->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'progress' => 0,
            'completed_at' => now(),
        ]);
    }
}
