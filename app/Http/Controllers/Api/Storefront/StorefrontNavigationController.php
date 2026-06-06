<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Repositories\Navigation\NavigationMenuRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StorefrontNavigationController extends Controller
{
    public function __construct(
        private NavigationMenuRepository $menuRepository
    ) {
    }

    /**
     * Get navigation menu by handle
     */
    public function show(Request $request, string $handle): JsonResponse
    {
        $store = request()->attributes->get('store');
        
        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
            ], 404);
        }

        // Cache the menu for 30 minutes
        $cacheKey = "storefront:navigation:store:{$store->id}:handle:{$handle}";
        $ttl = 1800;

        $menu = Cache::remember($cacheKey, $ttl, function () use ($store, $handle) {
            return $this->menuRepository->getByHandle($handle, $store->id);
        });

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Navigation menu not found',
            ], 404);
        }

        // Transform menu items into hierarchical structure
        $items = $menu->rootItems->map(function ($item) {
            return $this->transformMenuItem($item);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'handle' => $menu->handle,
                'items' => $items,
            ],
        ]);
    }

    /**
     * Transform menu item with children
     */
    private function transformMenuItem($item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'type' => $item->type,
            'url' => $item->url,
            'target' => $item->target,
            'resourceId' => $item->resource_id,
            'resourceType' => $item->resource_type,
            'children' => $item->children->map(function ($child) {
                return $this->transformMenuItem($child);
            })->toArray(),
        ];
    }
}
