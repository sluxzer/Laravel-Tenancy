<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessImportJob;
use Illuminate\Console\Command;

/**
 * Import Data Command
 *
 * CLI command to manually trigger data import from a file.
 */
class ImportDataCommand extends Command
{
    protected $signature = 'import:data
            {file}
            {type}
            {--mapping=}
            {--skip-duplicates}';

    protected $description = 'Trigger data import job manually';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $type = $this->argument('type');
        $mapping = $this->option('mapping') ? json_decode($this->option('mapping'), true) : [];
        $skipDuplicates = $this->option('skip-duplicates', false);

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        // Create import job (simplified - in real implementation you'd store job in database)
        $jobId = uniqid('', true);
        $this->info("Import job created: {$jobId}");
        ProcessImportJob::dispatch($jobId);

        $this->info('Import job queued successfully');

        return 0;
    }
}
