<?php

namespace App\Actions\Theme;

use App\Repositories\Theme\ThemeSectionRepository;

class ReorderSectionsAction
{
    public function __construct(
        private ThemeSectionRepository $sectionRepository
    ) {
    }

    public function execute(array $sectionIds): void
    {
        $this->sectionRepository->reorder($sectionIds);
    }
}
