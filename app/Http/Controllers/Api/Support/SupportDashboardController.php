<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Support Dashboard Controller
 * 
 * Wave 6: Support authority domain controller.
 * Support authority is a SUBSET of platform authority.
 */
class SupportDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Wave 6: Stub implementation
        // Future: Implement support dashboard
        return response()->json([
            'message' => 'Support dashboard',
            'wave' => 6,
            'authority_domain' => 'support',
        ]);
    }
}
