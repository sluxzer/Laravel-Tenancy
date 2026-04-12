<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RunScheduledReportJob;
use App\Models\ScheduledReport;
use App\Services\ReportService;
use Illuminate\Console\Command;

/**
 * Generate Reports Command
 *
 * CLI command to manually generate and run scheduled reports.
 */
class GenerateReportsCommand extends Command
{
    protected $signature = 'reports:generate
            {reportId}
            {--async}';

    protected $description = 'Generate a scheduled report immediately';

    public function handle(): int
    {
        $reportId = $this->argument('reportId');
        $async = $this->option('async', false);

        $report = ScheduledReport::find($reportId);
        if (! $report) {
            $this->error("Scheduled report not found: {$reportId}");

            return 1;
        }

        if ($async) {
            RunScheduledReportJob::dispatch($report);
            $this->info("Report generation queued: {$reportId}");
        } else {
            $reportService = app(ReportService::class);
            $reportService->runReport(
                $report->customReport,
                [],
                'manual',
                $report->created_by
            );
            $this->info("Report generated successfully: {$reportId}");
        }

        return 0;
    }
}
