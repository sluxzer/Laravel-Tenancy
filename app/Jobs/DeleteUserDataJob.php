<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GdprRequest;
use App\Models\User;
use App\Services\GdprService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Delete User Data Job
 *
 * Processes GDPR data deletion requests.
 */
class DeleteUserDataJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        protected GdprRequest $request,
    ) {}

    public function handle(GdprService $gdprService): void
    {
        try {
            $gdprService->processDeletion($this->request);

            Log::info('User data deletion completed', [
                'request_id' => $this->request->id,
                'user_id' => $this->request->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('User data deletion failed', [
                'request_id' => $this->request->id,
                'error' => $e->getMessage(),
            ]);

            $this->request->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
