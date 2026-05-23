<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PlatformFeatureController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Feature flags list']);
    }

    public function update(string $feature): JsonResponse
    {
        return response()->json(['message' => 'Feature flag updated', 'feature' => $feature]);
    }
}
