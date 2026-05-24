<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\JsonResponse;

trait ApiResponserTrait
{
    public static function success($data = null, string $message = 'success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __($message),
            'data' => $data,
        ], $statusCode);
    }

    public static function successWithMeta($data = null, array $meta = [], string $message = 'success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __($message),
            'data' => $data,
            'meta' => $meta,
        ], $statusCode);
    }

    public static function paginated(LengthAwarePaginator $paginator, $data, array $additionalMeta = [], string $message = 'success', int $code = 200): JsonResponse
    {
        $response = [
            'success'  => true,
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
            ],
        ];

        if (!empty($additionalMeta)) {
            $response['meta'] = array_merge($response['meta'], $additionalMeta);
        }

        return response()->json($response, $code);
    }

    public static function error(string $message = 'error', int $statusCode = 400, $data = null, string $errorCode = 'GEN_001'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $errorCode,
            'message' => __($message),
            'errors' => $data,
        ], $statusCode);
    }
}
