<?php

namespace App\Actions\Theme;

use App\DTOs\Theme\UpdateThemeDTO;
use App\Models\Theme\Theme;
use App\Repositories\Theme\ThemeRepository;
use Illuminate\Support\Str;

class UpdateThemeAction
{
    public function __construct(
        private ThemeRepository $themeRepository
    ) {
    }

    public function execute(Theme $theme, UpdateThemeDTO $dto): Theme
    {
        $data = $dto->toArray();

        // Auto-generate slug if name is being updated but slug is not provided
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $this->themeRepository->update($theme, $data);
    }
}
