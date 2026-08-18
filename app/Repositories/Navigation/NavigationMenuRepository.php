<?php

namespace App\Repositories\Navigation;

use App\Models\Navigation\NavigationMenu;
use Illuminate\Database\Eloquent\Collection;

class NavigationMenuRepository
{
    /**
     * Find menu by ID
     */
    public function find(int $id): ?NavigationMenu
    {
        return NavigationMenu::find($id);
    }

    /**
     * Find menu with items
     */
    public function findWithItems(int $id): ?NavigationMenu
    {
        return NavigationMenu::with(['rootItems' => function ($query) {
            $query->with('children');
        }])->find($id);
    }

    /**
     * Get all menus for a store
     */
    public function getAllForStore(int $storeId): Collection
    {
        return NavigationMenu::where('store_id', $storeId)
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get menu by handle for a store
     */
    public function getByHandle(string $handle, int $storeId): ?NavigationMenu
    {
        return NavigationMenu::where('store_id', $storeId)
            ->where('handle', $handle)
            // ⚠️ PERF FIX: transformNavigationItem() calls getResolvedUrl(),
            // which lazy-loads the polymorphic `resource` (page/category/
            // product) relation to build the link. Without eager loading
            // that was one extra query PER menu item (and per child item)
            // on every cache-miss navigation fetch. Eager-loading `resource`
            // batches it into one query per distinct resource type instead.
            ->with(['rootItems' => function ($query) {
                $query->active()->with(['resource', 'children' => function ($childQuery) {
                    $childQuery->active()->with('resource');
                }]);
            }])
            ->first();
    }

    /**
     * Create a new menu
     */
    public function create(array $data): NavigationMenu
    {
        return NavigationMenu::create($data);
    }

    /**
     * Update menu
     */
    public function update(NavigationMenu $menu, array $data): NavigationMenu
    {
        $menu->update($data);
        return $menu->fresh();
    }

    /**
     * Delete menu
     */
    public function delete(NavigationMenu $menu): bool
    {
        return $menu->delete();
    }

    /**
     * Find by handle for a store
     */
    public function findByHandle(int $storeId, string $handle): ?NavigationMenu
    {
        return NavigationMenu::where('store_id', $storeId)
            ->where('handle', $handle)
            ->first();
    }
}
