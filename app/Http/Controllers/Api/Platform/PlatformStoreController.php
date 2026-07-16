<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Enums\Store\StoreStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformStoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(100, max(1, (int) $request->get('per_page', 25)));
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $query = Store::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $total = $query->count();
        $offset = ($page - 1) * $perPage;
        $lastPage = (int) ceil($total / $perPage);

        $stores = $query
            ->with('owner')
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'domain' => $store->domain,
                'status' => $store->status->value,
                'is_active' => $store->is_active,
                'owner' => $store->owner ? [
                    'id' => $store->owner->id,
                    'name' => $store->owner->name,
                    'email' => $store->owner->email,
                ] : null,
                'created_at' => $store->created_at->toISOString(),
                'updated_at' => $store->updated_at->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $stores,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function show(Store $store): JsonResponse
    {
        $productsCount = $store->products()->count();
        $ordersCount = $store->orders()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'domain' => $store->domain,
                'status' => $store->status->value,
                'is_active' => $store->is_active,
                'owner' => $store->owner ? [
                    'id' => $store->owner->id,
                    'name' => $store->owner->name,
                    'email' => $store->owner->email,
                ] : null,
                'products_count' => $productsCount,
                'orders_count' => $ordersCount,
                'stats' => [
                    'total_products' => $productsCount,
                    'total_orders' => $ordersCount,
                    'total_revenue' => 0, // TODO: Calculate from orders
                    'total_customers' => 0, // TODO: Calculate unique customers
                ],
                'created_at' => $store->created_at->toISOString(),
                'updated_at' => $store->updated_at->toISOString(),
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
