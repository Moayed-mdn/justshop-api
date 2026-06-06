<?php

namespace App\Actions\Theme;

use App\DTOs\Theme\UpdateSectionDTO;
use App\Models\Theme\ThemeSection;
use App\Repositories\Theme\ThemeSectionRepository;

class UpdateSectionAction
{
    public function __construct(
        private ThemeSectionRepository $sectionRepository
    ) {
    }

    public function execute(ThemeSection $section, UpdateSectionDTO $dto): ThemeSection
    {
        $data = $dto->toArray();

        return $this->sectionRepository->update($section, $data);
    }
}
