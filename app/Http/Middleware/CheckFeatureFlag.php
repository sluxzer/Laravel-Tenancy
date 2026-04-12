<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\FeatureFlag;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Check Feature Flag Middleware
 *
 * Verifies that required feature flags are enabled for the tenant.
 */
class CheckFeatureFlag
{
    protected string $featureKey;

    protected array $except = [];

    public function __construct(string $featureKey)
    {
        $this->featureKey = $featureKey;
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
        $feature = FeatureFlag::where('key', $this->featureKey)
            ->where('is_active', true)
            ->first();

        if (! $feature) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Feature not found',
                'error' => 'feature_not_found',
                'feature_key' => $this->featureKey,
            ], 404);
        }

        $isEnabled = $feature->isEnabledForTenant($tenant->id, $request->user()?->email ?? null);

        if (! $isEnabled) {
            return new JsonResponse([
                'success' => false,
                'message' => 'This feature is not available for your account.',
                'error' => 'feature_disabled',
                'feature_key' => $this->featureKey,
                'feature_name' => $feature->name,
            ], 403);
        }

        // Add feature to request for later use
        $request->merge(['feature' => $feature]);

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
