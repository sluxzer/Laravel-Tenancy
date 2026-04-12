<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Key Authentication Middleware
 *
 * Validates API keys for external API access.
 */
class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $apiKey = $request->header('X-API-Key')
            ?? $request->header('Authorization');

        if (! $apiKey) {
            return new JsonResponse([
                'success' => false,
                'message' => 'API key is required',
                'error' => 'api_key_missing',
            ], 401);
        }

        // Validate API key (implement your validation logic)
        if (! $this->validateApiKey($apiKey)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid API key',
                'error' => 'invalid_api_key',
            ], 401);
        }

        // Add API key user to request for later use
        $user = $this->getUserFromApiKey($apiKey);
        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        return $next($request);
    }

    /**
     * Validate API key.
     */
    protected function validateApiKey(string $apiKey): bool
    {
        // Implement your API key validation logic
        // This is a placeholder - implement based on your requirements
        return true;
    }

    /**
     * Get user from API key.
     */
    protected function getUserFromApiKey(string $apiKey): ?User
    {
        // Implement your user lookup logic
        // This is a placeholder - implement based on your requirements
        return null;
    }
}
