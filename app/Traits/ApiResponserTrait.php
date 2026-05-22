<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\JsonResponse;

trait ApiResponserTrait
{
    public static function success($data = null, string $message = 'success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => __($message),
            'data' => $data,
        ], $statusCode);
    }

    public static function paginated(LengthAwarePaginator $paginator, $data, array $additionalMeta = [], string $message = 'success', int $code = 200): JsonResponse
    {
        $response = [
            'status'  => true,
            'message' => __($message),
            'data'    => $data,
            'meta' => [
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => count($data),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                ],
                ...$additionalMeta
            ],
        ];

        if ($additionalMeta !== null) {
            $response['meta'] = array_merge($response['meta'], $additionalMeta);
        }

        return response()->json($response, $code);
    }

    public static function error(string $message = 'error', int $statusCode = 400, $data = null): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => __($message),
            'data' => $data,
        ], $statusCode);
    }
}