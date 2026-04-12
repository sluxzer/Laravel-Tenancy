<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Cleanup Expired Tokens Command
 *
 * CLI command to clean up expired personal access tokens.
 */
class CleanupExpiredTokensCommand extends Command
{
    protected $signature = 'auth:cleanup-tokens
            {--days=}';

    protected $description = 'Clean up expired personal access tokens';

    public function handle(): int
    {
        $days = (int) $this->option('days', 30);

        $deletedCount = PersonalAccessToken::where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deletedCount} expired tokens older than {$days} days");

        return 0;
    }
}
