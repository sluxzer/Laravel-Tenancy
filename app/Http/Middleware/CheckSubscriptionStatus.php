<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Check Subscription Status Middleware
 *
 * Verifies that the tenant/user has an active subscription.
 */
class CheckSubscriptionStatus
{
    protected SubscriptionService $subscriptionService;

    protected array $except = [];

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Skip middleware for routes in except array
        if ($this->shouldSkipMiddleware($request)) {
            return $next($request);
        }

        if (! tenancy()->initialized) {
            return $next($request);
        }

        $tenant = tenancy()->tenant;
        $subscription = $this->subscriptionService->getActiveTenantSubscription($tenant->id);

        if (! $subscription || ! $this->subscriptionService->isValid($subscription)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Subscription is inactive or expired. Please renew your subscription.',
                'error' => 'subscription_inactive',
                'subscription' => [
                    'id' => $subscription?->id,
                    'status' => $subscription?->status,
                    'current_period_end' => $subscription?->current_period_end,
                ],
            ], 403);
        }

        // Add subscription to request for later use
        $request->merge(['subscription' => $subscription]);

        return $next($request);
    }

    /**
     * Determine if the request should be skipped.
     */
    protected function shouldSkipMiddleware(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set routes to exclude from middleware.
     */
    public function except(array $routes): self
    {
        $this->except = $routes;

        return $this;
    }
}
