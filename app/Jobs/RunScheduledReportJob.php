<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ScheduledReport;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Run Scheduled Report Job
 *
 * Queue job for executing scheduled reports.
 */
class RunScheduledReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ScheduledReport $report,
    ) {
        $this->report = $report;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService): void
    {
        $reportService->runReport(
            $report->customReport,
            [],
            'scheduled',
            $report->created_by
        );

        // Update next run time
        if ($report->frequency !== 'custom') {
            $nextRun = match ($report->frequency) {
                'daily' => now()->addDay(),
                'weekly' => now()->addWeek(),
                'monthly' => now()->addMonth(),
                default => now()->addDay(),
            };
            $report->update(['next_run_at' => $nextRun]);
        }
    }
}
