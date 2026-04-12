<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessExportJob;
use App\Models\ExportJob;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Export Data Command
 *
 * CLI command to manually trigger data export.
 */
class ExportDataCommand extends Command
{
    protected $signature = 'export:data
            {tenantId}
            {type}
            {--format=}
            {--filters=}';

    protected $description = 'Trigger data export job manually';

    public function handle(): int
    {
        $tenantId = $this->argument('tenantId');
        $type = $this->argument('type');
        $format = $this->option('format', 'json');
        $filters = $this->option('filters') ? json_decode($this->option('filters'), true) : [];

        // Find tenant
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            $this->error('Tenant not found');

            return 1;
        }

        // Create export job
        $job = ExportJob::create([
            'tenant_id' => $tenant->id,
            'type' => $type,
            'format' => $format,
            'filters' => $filters,
            'requested_by' => Artisan::user()?->id,
            'status' => 'pending',
        ]);

        $this->info("Export job created: {$job->id}");
        ProcessExportJob::dispatch($job);

        return 0;
    }
}
