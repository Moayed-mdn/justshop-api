<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SupportUserLookupController extends Controller
{
    public function search(): JsonResponse
    {
        return response()->json(['message' => 'User search results']);
    }

    public function show(int $user): JsonResponse
    {
        return response()->json(['message' => 'User details', 'user_id' => $user]);
    }

    public function activity(int $user): JsonResponse
    {
        return response()->json(['message' => 'User activity']);
    }
}
