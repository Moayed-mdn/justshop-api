<?php

namespace App\Actions\Navigation;

use App\Models\Navigation\NavigationMenuItem;

class ReorderMenuItemsAction
{
    public function execute(array $itemIds): void
    {
        foreach ($itemIds as $position => $itemId) {
            NavigationMenuItem::where('id', $itemId)
                ->update(['position' => $position]);
        }
    }
}
