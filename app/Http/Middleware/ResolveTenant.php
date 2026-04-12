<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Tenancy;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException;

/**
 * Resolve Tenant Middleware
 *
 * Identifies and resolves the tenant from the request URL.
 * Supports path-based tenant identification: /api/{tenant}/...
 */
class ResolveTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            // Initialize tenancy from request path
            tenancy()->initialize(
                $request->getHost() ?? null,
                $request->getRequestUri()
            );
        } catch (TenantCouldNotBeIdentifiedException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or invalid',
                'error' => 'tenant_not_found',
            ], 404);
        }

        // Add tenant to request for later use
        if (tenancy()->initialized) {
            $request->merge(['tenant' => tenancy()->tenant]);
        }

        return $next($request);
    }

    /**
     * Terminate tenancy after request is processed.
     */
    public function terminate(Request $request, $response)
    {
        Tenancy::end();

        return $response;
    }
}
