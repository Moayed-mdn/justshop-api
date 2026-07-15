<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PlatformAnalyticsController extends Controller
{
    public function index(): JsonResponse
    {
        // Wave 6: Mock analytics data
        // TODO: Replace with real analytics from repositories
        
        // Generate mock data for the last 30 days
        $userGrowth = [];
        $storeGrowth = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $userGrowth[] = [
                'date' => $date,
                'count' => rand(10, 50) + ($i * 2),
            ];
            $storeGrowth[] = [
                'date' => $date,
                'count' => rand(1, 10) + floor($i / 3),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'userGrowth' => $userGrowth,
                'storeGrowth' => $storeGrowth,
                'revenueByStore' => [
                    ['storeName' => 'Store A', 'revenue' => 45000],
                    ['storeName' => 'Store B', 'revenue' => 32000],
                    ['storeName' => 'Store C', 'revenue' => 28000],
                    ['storeName' => 'Store D', 'revenue' => 20000],
                ],
                'storeStatus' => [
                    ['status' => 'Active', 'count' => 78],
                    ['status' => 'Suspended', 'count' => 5],
                    ['status' => 'Pending', 'count' => 2],
                ],
            ],
        ]);
    }
}
