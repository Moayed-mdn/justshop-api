<?php

namespace App\Actions\Theme;

use App\DTOs\Theme\CreateThemeDTO;
use App\Models\Theme\Theme;
use App\Repositories\Theme\ThemeRepository;
use Illuminate\Support\Str;

class CreateThemeAction
{
    public function __construct(
        private ThemeRepository $themeRepository
    ) {
    }

    public function execute(CreateThemeDTO $dto): Theme
    {
        $data = $dto->toArray();

        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($dto->name);
        }

        return $this->themeRepository->create($data);
    }
}
