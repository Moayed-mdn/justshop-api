<?php

namespace App\Actions\Theme;

use App\DTOs\Theme\CreateSectionDTO;
use App\Models\Theme\ThemeSection;
use App\Repositories\Theme\ThemeSectionRepository;

class CreateSectionAction
{
    public function __construct(
        private ThemeSectionRepository $sectionRepository
    ) {
    }

    public function execute(CreateSectionDTO $dto): ThemeSection
    {
        $data = $dto->toArray();

        // Auto-assign position if not provided
        if ($data['position'] === null) {
            $data['position'] = $this->sectionRepository->getNextPosition($dto->themeId);
        }

        return $this->sectionRepository->create($data);
    }
}
