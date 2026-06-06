<?php

namespace App\Actions\Theme;

use App\Repositories\Theme\ThemeBlockRepository;

class ReorderBlocksAction
{
    public function __construct(
        private ThemeBlockRepository $blockRepository
    ) {
    }

    public function execute(array $blockIds): void
    {
        $this->blockRepository->reorder($blockIds);
    }
}
