<?php

namespace App\Actions\Navigation;

use App\DTOs\Navigation\CreateMenuDTO;
use App\Models\Navigation\NavigationMenu;
use App\Repositories\Navigation\NavigationMenuRepository;
use Illuminate\Support\Str;

class CreateNavigationMenuAction
{
    public function __construct(
        private NavigationMenuRepository $menuRepository
    ) {
    }

    public function execute(CreateMenuDTO $dto): NavigationMenu
    {
        $data = $dto->toArray();

        // Auto-generate handle if not provided
        if (empty($data['handle'])) {
            $data['handle'] = Str::slug($dto->name);
        }

        return $this->menuRepository->create($data);
    }
}
