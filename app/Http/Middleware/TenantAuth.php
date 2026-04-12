<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant Authentication Middleware
 *
 * Ensures the user belongs to the tenant making the request.
 */
class TenantAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! tenancy()->initialized) {
            return $next($request);
        }

        $tenant = tenancy()->tenant;

        // Check if user belongs to this tenant
        $userBelongsToTenant = $user->tenants()
            ->where('id', $tenant->id)
            ->exists();

        if (! $userBelongsToTenant) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You do not have access to this tenant.',
                'error' => 'tenant_access_denied',
            ], 403);
        }

        return $next($request);
    }
}
