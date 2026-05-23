<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SupportStoreLookupController extends Controller
{
    public function search(): JsonResponse
    {
        return response()->json(['message' => 'Store search results']);
    }

    public function show(int $store): JsonResponse
    {
        return response()->json(['message' => 'Store details', 'store_id' => $store]);
    }

    public function activity(int $store): JsonResponse
    {
        return response()->json(['message' => 'Store activity']);
    }
}
