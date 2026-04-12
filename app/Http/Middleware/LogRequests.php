<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Log Requests Middleware
 *
 * Logs all incoming API requests for monitoring and debugging.
 */
class LogRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $startTime = microtime(true);

        // Log request details
        Log::info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tenant_id' => tenancy()->initialized ? tenancy()->tenant->id : null,
            'user_id' => $request->user()?->id,
            'request_id' => $request->header('X-Request-ID'),
        ]);

        $response = $next($request);

        // Log response details
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        Log::info('API Response', [
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'request_id' => $request->header('X-Request-ID'),
        ]);

        return $response;
    }

    /**
     * Generate a unique request ID.
     */
    protected function generateRequestId(): string
    {
        return uniqid('', true);
    }
}
