<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PlatformAnalyticsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Platform analytics']);
    }
}
