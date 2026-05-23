<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Platform Dashboard Controller
 * 
 * Wave 6: Platform authority domain controller.
 * Platform authority is INDEPENDENT from merchant authority.
 */
class PlatformDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Wave 6: Stub implementation
        // Future: Implement platform dashboard
        return response()->json([
            'message' => 'Platform dashboard',
            'wave' => 6,
            'authority_domain' => 'platform',
        ]);
    }
}
