<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Activity;
use App\Models\AnalyticsEvent;
use App\Models\AuditLog;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Cleanup Old Data Job
 *
 * Queue job for cleaning up old logs, notifications, and temporary files.
 */
class CleanupOldDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $daysToKeep = 90,
    ) {
        $this->daysToKeep = $daysToKeep;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoffDate = now()->subDays($this->daysToKeep);

        // Clean up old audit logs
        $deletedLogs = AuditLog::where('created_at', '<', $cutoffDate)->delete();

        // Clean up old activity logs
        $deletedActivities = Activity::where('created_at', '<', $cutoffDate)->delete();

        // Clean up old webhook events
        $deletedWebhookEvents = WebhookEvent::where('created_at', '<', $cutoffDate)->delete();

        // Clean up old analytics events
        $deletedAnalyticsEvents = AnalyticsEvent::where('created_at', '<', $cutoffDate)->delete();

        // Clean up temporary files older than cutoff
        $deletedTempFiles = \Storage::disk('local')->allFiles('imports/temp')
            ->filter(fn ($file) => now()->diffInDays(\Storage::lastModified($file)) > 1)
            ->each(fn ($file) => \Storage::delete($file));

        // Log cleanup
        \Log::info('Cleanup old data job completed', [
            'cutoff_date' => $cutoffDate,
            'deleted_audit_logs' => $deletedLogs,
            'deleted_activities' => $deletedActivities,
            'deleted_webhook_events' => $deletedWebhookEvents,
            'deleted_analytics_events' => $deletedAnalyticsEvents,
            'deleted_temp_files' => count($deletedTempFiles),
        ]);
    }
}
