<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;

/**
 * Rate Limit Middleware
 *
 * Provides rate limiting for API endpoints.
 * Can be applied globally or to specific routes.
 */
class RateLimit
{
    protected ?string $limiterName = null;

    protected int $maxAttempts = 60;

    protected int $decayMinutes = 1;

    public function __construct(?string $limiterName = null, ?int $maxAttempts = null, ?int $decayMinutes = null)
    {
        $this->limiterName = $limiterName;
        $this->maxAttempts = $maxAttempts ?? 60;
        $this->decayMinutes = $decayMinutes ?? 1;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiterFacade::tooManyAttempts($key, $this->maxAttempts, $this->decayMinutes)) {
            $retryAfter = RateLimiterFacade::availableIn($key, $this->decayMinutes);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'error' => 'rate_limit_exceeded',
                'retry_after' => $retryAfter->toIso8601String(),
            ], 429)->header('Retry-After', $retryAfter->toIso8601String())
                ->header('X-RateLimit-Limit', (string) $this->maxAttempts)
                ->header('X-RateLimit-Remaining', '0');
        }

        // Record successful attempt
        RateLimiterFacade::hit($key, $this->decayMinutes);

        $response = $next($request);
        $remaining = RateLimiterFacade::retriesLeft($key, $this->maxAttempts, $this->decayMinutes);

        return $response->header('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->header('X-RateLimit-Remaining', (string) $remaining);
    }

    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($this->limiterName) {
            return sha1($this->limiterName.':'.$request->ip().'|'.$request->user()?->id.'|'.$request->route()?->getName());
        }

        // Default rate limiting by IP
        return 'global:'.$request->ip();
    }

    /**
     * Rate limit by tenant.
     */
    protected function limitByTenant(string $tenantId): string
    {
        return 'tenant:'.$tenantId;
    }

    /**
     * Rate limit by user.
     */
    protected function limitByUser(int $userId): string
    {
        return 'user:'.$userId;
    }
}
