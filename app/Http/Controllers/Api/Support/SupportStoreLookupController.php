<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class SupportStoreLookupController extends Controller
{
    public function search(): JsonResponse
    {
        return response()->json(['message' => 'Store search results']);
    }

    public function show(Store $store): JsonResponse
    {
        return response()->json(['message' => 'Store details', 'store_id' => $store->id]);
    }

    public function activity(Store $store): JsonResponse
    {
        return response()->json(['message' => 'Store activity']);
    }
}
