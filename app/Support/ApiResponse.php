<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        string $message,
        mixed $data = null,
        int $status = 200,
        ?array $meta = null,
        ?array $links = null,
    ): JsonResponse {
        $body = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ];

        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        if ($links !== null) {
            $body['links'] = $links;
        }

        return response()->json($body, $status);
    }

    public static function error(string $message, int $status, ?array $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $message): JsonResponse
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];

        $body = self::success($message, $paginator->items(), meta: $meta)->getData(true);
        $legacyPagination = $paginator->toArray();
        unset($legacyPagination['data']);

        // Keep the paginator's original top-level metadata for existing
        // clients while also exposing the normalized meta object.
        return response()->json(array_merge($body, $legacyPagination, ['meta' => $meta]));
    }
}
