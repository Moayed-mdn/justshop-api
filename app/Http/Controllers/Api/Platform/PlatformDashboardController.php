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
        // Wave 6: Mock implementation for frontend development
        // TODO: Replace with real data from repositories
        
        return response()->json([
            'success' => true,
            'data' => [
                'totalUsers' => 1250,
                'totalStores' => 85,
                'totalRevenue' => 125000.50,
                'totalLeads' => 42,
                'usersTrend' => [
                    'change' => 12.5,
                    'direction' => 'up',
                ],
                'storesTrend' => [
                    'change' => 8.3,
                    'direction' => 'up',
                ],
                'revenueTrend' => [
                    'change' => 15.7,
                    'direction' => 'up',
                ],
                'leadsTrend' => [
                    'change' => -3.2,
                    'direction' => 'down',
                ],
            ],
        ]);
    }

    public function cmsStats(): JsonResponse
    {
        // Wave 6: Mock CMS statistics
        // TODO: Replace with real data from CMS repositories
        
        return response()->json([
            'success' => true,
            'data' => [
                'blog' => [
                    'total' => 35,
                    'published' => 25,
                    'draft' => 8,
                    'archived' => 2,
                ],
                'pages' => [
                    'total' => 18,
                    'published' => 15,
                    'draft' => 2,
                    'archived' => 1,
                ],
                'docs' => [
                    'total' => 25,
                    'published' => 20,
                    'draft' => 4,
                    'archived' => 1,
                ],
            ],
        ]);
    }
}
