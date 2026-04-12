<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Check Permissions Middleware
 *
 * Verifies that the authenticated user has the required permissions.
 */
class CheckPermissions
{
    protected array $permissions;

    public function __construct(...$permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication required',
                'error' => 'unauthenticated',
            ], 401);
        }

        foreach ($this->permissions as $permission) {
            if (! $user->can($permission)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'You do not have permission to access this resource',
                    'error' => 'permission_denied',
                    'required_permission' => $permission,
                ], 403);
            }
        }

        return $next($request);
    }
}
