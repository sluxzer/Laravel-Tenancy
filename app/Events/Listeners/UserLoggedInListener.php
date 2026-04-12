<?php

declare(strict_types=1);

namespace App\Events\Listeners;

use App\Events\UserLoggedIn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * User Logged In Listener
 *
 * Broadcasts event when a user logs in.
 */
class UserLoggedInListener implements ShouldQueue
{
    public function __construct()
    {
        Log::info('UserLoggedInListener listening');
    }

    public function handle(UserLoggedIn $event): void
    {
        $userId = $event->userId;

        Log::info("User {$userId} logged in from IP {$event->ipAddress}");
    }
}
