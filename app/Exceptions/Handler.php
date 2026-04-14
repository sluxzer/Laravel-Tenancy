<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException as LaravelAuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends \Illuminate\Foundation\Exceptions\Handler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (DomainException $e, Request $request) {
            return $this->jsonResponse($e->render(), $e->getHttpStatus());
        });

        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Resource not found',
                'error_code' => 'NOT_FOUND',
            ], 404);
        });

        $this->renderable(function (LaravelValidationException $e, Request $request) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Validation failed',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ], 422);
        });

        $this->renderable(function (LaravelAuthenticationException $e, Request $request) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        });

        $this->renderable(function (AuthorizationException $e, Request $request) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ], $e->getHttpStatus());
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (config('app.debug')) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'INTERNAL_ERROR',
                    'trace' => $e->getTraceAsString(),
                ], 500);
            }

            return $this->jsonResponse([
                'success' => false,
                'message' => 'An internal error occurred',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        });
    }

    /**
     * Create a JSON response.
     */
    protected function jsonResponse(array $data, int $status): JsonResponse
    {
        return response()->json($data, $status);
    }
}
