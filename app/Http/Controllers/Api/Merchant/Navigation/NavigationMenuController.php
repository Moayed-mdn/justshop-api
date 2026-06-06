<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Navigation;

use App\Actions\Navigation\CreateNavigationMenuAction;
use App\Actions\Navigation\UpdateNavigationMenuAction;
use App\DTOs\Navigation\CreateMenuDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\Navigation\CreateMenuRequest;
use App\Http\Requests\Merchant\Navigation\UpdateMenuRequest;
use App\Http\Resources\Navigation\NavigationMenuResource;
use App\Models\Navigation\NavigationMenu;
use App\Models\Store;
use App\Repositories\Navigation\NavigationMenuRepository;
use Illuminate\Http\JsonResponse;

class NavigationMenuController extends Controller
{
    public function __construct(
        private NavigationMenuRepository $menuRepository,
        private CreateNavigationMenuAction $createMenuAction,
        private UpdateNavigationMenuAction $updateMenuAction,
    ) {
    }

    /**
     * Get all navigation menus for a store
     */
    public function index(Store $store): JsonResponse
    {
        $menus = $this->menuRepository->getAllForStore($store->id);

        return $this->success(NavigationMenuResource::collection($menus));
    }

    /**
     * Get a single navigation menu
     */
    public function show(Store $store, NavigationMenu $menu): JsonResponse
    {
        $menu = $this->menuRepository->findWithItems($menu->id);

        return $this->success(new NavigationMenuResource($menu));
    }

    /**
     * Create a new navigation menu
     */
    public function store(CreateMenuRequest $request, Store $store): JsonResponse
    {
        $dto = CreateMenuDTO::fromArray(
            array_merge($request->validated(), ['store_id' => $store->id])
        );
        
        $menu = $this->createMenuAction->execute($dto);

        return $this->success(
            new NavigationMenuResource($menu),
            __('theme.menu_created'),
            201
        );
    }

    /**
     * Update a navigation menu
     */
    public function update(UpdateMenuRequest $request, Store $store, NavigationMenu $menu): JsonResponse
    {
        $menu = $this->updateMenuAction->execute($menu, $request->validated());

        return $this->success(
            new NavigationMenuResource($menu),
            __('theme.menu_updated')
        );
    }

    /**
     * Delete a navigation menu
     */
    public function destroy(Store $store, NavigationMenu $menu): JsonResponse
    {
        $this->menuRepository->delete($menu);

        return $this->success(null, __('theme.menu_deleted'));
    }
}
