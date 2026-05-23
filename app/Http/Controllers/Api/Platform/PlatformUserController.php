<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PlatformUserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Platform users list']);
    }

    public function show(int $user): JsonResponse
    {
        return response()->json(['message' => 'User details', 'user_id' => $user]);
    }

    public function suspend(int $user): JsonResponse
    {
        return response()->json(['message' => 'User suspended']);
    }

    public function activate(int $user): JsonResponse
    {
        return response()->json(['message' => 'User activated']);
    }
}
