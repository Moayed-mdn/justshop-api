<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PlatformAuditController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Audit logs list']);
    }

    public function show(int $log): JsonResponse
    {
        return response()->json(['message' => 'Audit log details', 'log_id' => $log]);
    }
}
