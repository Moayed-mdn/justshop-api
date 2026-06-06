<?php

namespace App\Actions\Theme;

use App\DTOs\Theme\CreateBlockDTO;
use App\Models\Theme\ThemeBlock;
use App\Repositories\Theme\ThemeBlockRepository;

class CreateBlockAction
{
    public function __construct(
        private ThemeBlockRepository $blockRepository
    ) {
    }

    public function execute(CreateBlockDTO $dto): ThemeBlock
    {
        $data = $dto->toArray();

        // Auto-assign position if not provided
        if ($data['position'] === null) {
            $data['position'] = $this->blockRepository->getNextPosition($dto->sectionId);
        }

        return $this->blockRepository->create($data);
    }
}
