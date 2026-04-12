<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\UsageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Update Usage Metrics Job
 *
 * Queue job for aggregating and updating usage metrics.
 */
class UpdateUsageMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public string $metricType,
    ) {
        $this->tenantId = $tenantId;
        $this->metricType = $metricType;
    }

    /**
     * Execute the job.
     */
    public function handle(UsageService $usageService): void
    {
        $usageService->aggregateMetrics($this->tenantId, $this->metricType);
    }
}
