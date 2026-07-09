<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Navigation;

use App\Actions\Navigation\CreateMenuItemAction;
use App\Actions\Navigation\ReorderMenuItemsAction;
use App\Actions\Navigation\UpdateMenuItemAction;
use App\DTOs\Navigation\CreateMenuItemDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\Navigation\CreateMenuItemRequest;
use App\Http\Requests\Merchant\Navigation\UpdateMenuItemRequest;
use App\Http\Resources\Navigation\NavigationMenuItemResource;
use App\Models\Navigation\NavigationMenu;
use App\Models\Navigation\NavigationMenuItem;
use App\Models\Store;
use App\Policies\NavigationPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavigationMenuItemController extends Controller
{
    public function __construct(
        private CreateMenuItemAction $createMenuItemAction,
        private UpdateMenuItemAction $updateMenuItemAction,
        private ReorderMenuItemsAction $reorderMenuItemsAction,
    ) {
    }

    /**
     * Create a new menu item
     */
    public function store(
        CreateMenuItemRequest $request,
        Store $store,
        NavigationMenu $menu,
    ): JsonResponse {
        $this->authorize('create', [NavigationPolicy::class, $store]);

        $dto = CreateMenuItemDTO::fromArray(
            array_merge($request->validated(), ['menu_id' => $menu->id])
        );
        
        $menuItem = $this->createMenuItemAction->execute($dto);

        return $this->success(
            new NavigationMenuItemResource($menuItem),
            __('theme.menu_item_created'),
            201
        );
    }

    /**
     * Update a menu item
     */
    public function update(
        UpdateMenuItemRequest $request,
        Store $store,
        NavigationMenu $menu,
        NavigationMenuItem $item,
    ): JsonResponse {
        $this->authorize('update', [NavigationPolicy::class, $store]);

        $item = $this->updateMenuItemAction->execute($item, $request->validated());

        return $this->success(
            new NavigationMenuItemResource($item),
            __('theme.menu_item_updated')
        );
    }

    /**
     * Delete a menu item
     */
    public function destroy(Store $store, NavigationMenu $menu, NavigationMenuItem $item): JsonResponse
    {
        $this->authorize('delete', [NavigationPolicy::class, $store]);

        $item->delete();

        return $this->success(null, __('theme.menu_item_deleted'));
    }

    /**
     * Reorder menu items
     */
    public function reorder(Request $request, Store $store, NavigationMenu $menu): JsonResponse
    {
        $this->authorize('update', [NavigationPolicy::class, $store]);

        $validated = $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['required', 'integer', 'exists:navigation_menu_items,id'],
        ]);

        $this->reorderMenuItemsAction->execute($validated['item_ids']);

        return $this->success(null, __('theme.menu_items_reordered'));
    }
}
