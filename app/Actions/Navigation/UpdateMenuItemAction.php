<?php

namespace App\Actions\Navigation;

use App\Models\Navigation\NavigationMenuItem;

class UpdateMenuItemAction
{
    public function execute(NavigationMenuItem $item, array $data): NavigationMenuItem
    {
        $item->update($data);
        
        return $item->fresh();
    }
}
