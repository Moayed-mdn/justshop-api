<?php

namespace App\Actions\Navigation;

use App\Models\Navigation\NavigationMenu;
use App\Repositories\Navigation\NavigationMenuRepository;
use Illuminate\Support\Str;

class UpdateNavigationMenuAction
{
    public function __construct(
        private NavigationMenuRepository $menuRepository
    ) {
    }

    public function execute(NavigationMenu $menu, array $data): NavigationMenu
    {
        // Auto-generate handle if name is being updated but handle is not provided
        if (isset($data['name']) && !isset($data['handle'])) {
            $data['handle'] = Str::slug($data['name']);
        }

        return $this->menuRepository->update($menu, $data);
    }
}
