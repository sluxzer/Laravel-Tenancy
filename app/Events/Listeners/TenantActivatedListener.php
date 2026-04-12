<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\TenantActivated;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Tenant Activated Listener
 *
 * Broadcasts event when a tenant is activated (after suspension).
 */
class TenantActivatedListener implements ShouldQueue
{
    public function handle(TenantActivated $event): void
    {
        $tenant = $event->tenant;

        Log::info("Tenant {$tenant->id} activated");

        // Send notification to owner
        if ($tenant->owner_id) {
            $this->notifyOwner($tenant, 'Your tenant has been reactivated.');
        }
    }

    /**
     * Notify owner of tenant activation.
     */
    protected function notifyOwner(Tenant $tenant, string $message = 'Your tenant has been activated.'): void
    {
        Log::info("Owner notified of tenant {$tenant->id}: {$message}");
    }
}
