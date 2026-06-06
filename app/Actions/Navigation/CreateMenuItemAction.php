<?php

namespace App\Actions\Navigation;

use App\DTOs\Navigation\CreateMenuItemDTO;
use App\Models\Navigation\NavigationMenuItem;

class CreateMenuItemAction
{
    public function execute(CreateMenuItemDTO $dto): NavigationMenuItem
    {
        return NavigationMenuItem::create($dto->toArray());
    }
}
