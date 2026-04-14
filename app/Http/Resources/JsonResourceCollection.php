<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class JsonResourceCollection
{
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass
    ): array {
        return [
            'success' => true,
            'data' => $resourceClass::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    public static function single(JsonResource $resource): array
    {
        return [
            'success' => true,
            'data' => $resource->resolve(),
        ];
    }

    public static function success(string $message, mixed $data = null): array
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }

    public static function error(string $message, int $code = 400, ?array $errors = null): array
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $response;
    }
}
