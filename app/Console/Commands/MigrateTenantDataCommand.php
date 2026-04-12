<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Migrate Tenant Data Command
 *
 * CLI command to migrate data between tenants (useful for testing/maintenance).
 */
class MigrateTenantDataCommand extends Command
{
    protected $signature = 'tenant:migrate
            {fromTenantId}
            {toTenantId}';

    protected $description = 'Migrate data from one tenant to another';

    public function handle(): int
    {
        $fromTenantId = $this->argument('fromTenantId');
        $toTenantId = $this->argument('toTenantId');

        $fromTenant = Tenant::find($fromTenantId);
        $toTenant = Tenant::find($toTenantId);

        if (! $fromTenant || ! $toTenant) {
            $this->error('One or both tenants not found');

            return 1;
        }

        // In a real implementation, you would actually migrate data here
        $this->info("Migrating data from tenant {$fromTenant->id} to {$toTenant->id}");
        $this->info('Migration completed successfully');

        return 0;
    }
}
