<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class PlatformStoreController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Platform stores list']);
    }

    public function show(Store $store): JsonResponse
    {
        return response()->json(['message' => 'Store details', 'store_id' => $store->id]);
    }

    public function suspend(Store $store): JsonResponse
    {
        return response()->json(['message' => 'Store suspended']);
    }

    public function activate(Store $store): JsonResponse
    {
        return response()->json(['message' => 'Store activated']);
    }
}
