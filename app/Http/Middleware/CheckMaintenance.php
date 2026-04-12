<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Maintenance Mode Middleware
 *
 * Checks if the tenant or platform is in maintenance mode.
 */
class CheckMaintenance
{
    protected string $cacheKey = 'maintenance_mode';

    protected string $platformCacheKey = 'platform_maintenance_mode';

    protected array $except = [];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Skip middleware for routes in except array
        if ($this->shouldSkipMiddleware($request)) {
            return $next($request);
        }

        // Check platform-level maintenance first
        if (Cache::get($this->platformCacheKey)) {
            return $this->maintenanceResponse('Platform maintenance mode is active. Please try again later.');
        }

        // Check tenant-level maintenance
        if (tenancy()->initialized) {
            $tenant = tenancy()->tenant;
            $tenantMaintenanceKey = $this->cacheKey.':'.$tenant->id;

            if (Cache::get($tenantMaintenanceKey)) {
                return $this->maintenanceResponse('Tenant maintenance mode is active. Please try again later.');
            }
        }

        return $next($request);
    }

    /**
     * Return maintenance mode response.
     */
    protected function maintenanceResponse(string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'error' => 'maintenance_mode',
        ], 503);
    }

    /**
     * Enable platform maintenance mode.
     */
    public static function enablePlatformMaintenance(int $durationInMinutes = 60): void
    {
        Cache::put(
            'platform_maintenance_mode',
            true,
            now()->addMinutes($durationInMinutes)
        );
    }

    /**
     * Disable platform maintenance mode.
     */
    public static function disablePlatformMaintenance(): void
    {
        Cache::forget('platform_maintenance_mode');
    }

    /**
     * Enable tenant maintenance mode.
     */
    public static function enableTenantMaintenance(string $tenantId, int $durationInMinutes = 60): void
    {
        Cache::put(
            'maintenance_mode:'.$tenantId,
            true,
            now()->addMinutes($durationInMinutes)
        );
    }

    /**
     * Disable tenant maintenance mode.
     */
    public static function disableTenantMaintenance(string $tenantId): void
    {
        Cache::forget('maintenance_mode:'.$tenantId);
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
