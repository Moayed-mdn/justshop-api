<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PlatformAnalyticsController extends Controller
{
    public function index(): JsonResponse
    {
        // Generate real data for the last 30 days
        $userGrowth = [];
        $storeGrowth = [];
        
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            
            // Count users created on this date
            $userCount = User::whereDate('created_at', $dateStr)->count();
            $userGrowth[] = [
                'date' => $dateStr,
                'count' => $userCount,
            ];
            
            // Count stores created on this date
            $storeCount = Store::whereDate('created_at', $dateStr)->count();
            $storeGrowth[] = [
                'date' => $dateStr,
                'count' => $storeCount,
            ];
        }

        // Get top stores by product count (revenue tracking not implemented yet)
        $revenueByStore = Store::query()
            ->withCount('products')
            ->orderByDesc('products_count')
            ->limit(5)
            ->get()
            ->map(fn($store) => [
                'storeName' => $store->name,
                'revenue' => 0, // TODO: Calculate from orders when revenue tracking is implemented
            ])
            ->toArray();

        // Get store status distribution
        $storeStatus = DB::table('stores')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn($row) => [
                'status' => ucfirst($row->status),
                'count' => $row->count,
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'userGrowth' => $userGrowth,
                'storeGrowth' => $storeGrowth,
                'revenueByStore' => $revenueByStore,
                'storeStatus' => $storeStatus,
            ],
        ]);
    }
}

