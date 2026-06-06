<?php

namespace App\Actions\Theme;

use App\DTOs\Theme\UpdateBlockDTO;
use App\Models\Theme\ThemeBlock;
use App\Repositories\Theme\ThemeBlockRepository;

class UpdateBlockAction
{
    public function __construct(
        private ThemeBlockRepository $blockRepository
    ) {
    }

    public function execute(ThemeBlock $block, UpdateBlockDTO $dto): ThemeBlock
    {
        $data = $dto->toArray();

        return $this->blockRepository->update($block, $data);
    }
}
