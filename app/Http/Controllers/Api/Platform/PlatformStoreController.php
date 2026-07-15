<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class PlatformStoreController extends Controller
{
    public function index(): JsonResponse
    {
        // Wave 6: Mock implementation for frontend development
        // TODO: Replace with real store repository queries
        
        // Generate mock stores
        $stores = [];
        $statuses = ['active', 'suspended', 'pending'];
        
        for ($i = 1; $i <= 35; $i++) {
            $stores[] = [
                'id' => $i,
                'name' => 'Store ' . $i,
                'slug' => 'store-' . $i,
                'domain' => 'store' . $i . '.example.com',
                'status' => $i % 8 === 0 ? 'suspended' : ($i % 10 === 0 ? 'pending' : 'active'),
                'owner_name' => 'Owner ' . $i,
                'owner_email' => 'owner' . $i . '@example.com',
                'plan' => $i % 3 === 0 ? 'enterprise' : ($i % 2 === 0 ? 'pro' : 'basic'),
                'created_at' => now()->subDays(rand(1, 365))->toISOString(),
                'updated_at' => now()->subDays(rand(0, 30))->toISOString(),
            ];
        }
        
        // Simple pagination mock
        $page = (int) request()->get('page', 1);
        $perPage = (int) request()->get('per_page', 25);
        $total = count($stores);
        $offset = ($page - 1) * $perPage;
        
        $paginatedStores = array_slice($stores, $offset, $perPage);
        
        return response()->json([
            'success' => true,
            'data' => $paginatedStores,
            'meta' => [
                'current_page' => (int) $page,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => (int) $perPage,
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function show(Store $store): JsonResponse
    {
        // Wave 6: Mock store details
        // TODO: Replace with real store repository query
        
        $storeId = $store->id ?? request()->route('store');
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $storeId,
                'name' => 'Store ' . $storeId,
                'slug' => 'store-' . $storeId,
                'domain' => 'store' . $storeId . '.example.com',
                'status' => 'active',
                'owner_name' => 'Owner ' . $storeId,
                'owner_email' => 'owner' . $storeId . '@example.com',
                'plan' => 'pro',
                'products_count' => rand(10, 500),
                'orders_count' => rand(0, 1000),
                'revenue' => rand(1000, 100000),
                'created_at' => now()->subDays(rand(1, 365))->toISOString(),
                'updated_at' => now()->subDays(rand(0, 30))->toISOString(),
                'last_order_at' => now()->subDays(rand(0, 7))->toISOString(),
            ],
        ]);
    }

    public function suspend(Store $store): JsonResponse
    {
        // Wave 6: Mock suspend
        // TODO: Implement actual suspend logic
        
        $storeId = $store->id ?? request()->route('store');
        
        return response()->json([
            'success' => true,
            'message' => 'Store suspended successfully',
            'data' => [
                'id' => $storeId,
                'name' => 'Store ' . $storeId,
                'slug' => 'store-' . $storeId,
                'domain' => 'store' . $storeId . '.example.com',
                'status' => 'suspended',
                'owner_name' => 'Owner ' . $storeId,
                'owner_email' => 'owner' . $storeId . '@example.com',
                'plan' => 'pro',
                'created_at' => now()->subDays(rand(1, 365))->toISOString(),
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }

    public function activate(Store $store): JsonResponse
    {
        // Wave 6: Mock activate
        // TODO: Implement actual activate logic
        
        $storeId = $store->id ?? request()->route('store');
        
        return response()->json([
            'success' => true,
            'message' => 'Store activated successfully',
            'data' => [
                'id' => $storeId,
                'name' => 'Store ' . $storeId,
                'slug' => 'store-' . $storeId,
                'domain' => 'store' . $storeId . '.example.com',
                'status' => 'active',
                'owner_name' => 'Owner ' . $storeId,
                'owner_email' => 'owner' . $storeId . '@example.com',
                'plan' => 'pro',
                'created_at' => now()->subDays(rand(1, 365))->toISOString(),
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }
}
