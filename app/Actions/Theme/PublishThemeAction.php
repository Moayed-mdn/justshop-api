<?php

namespace App\Actions\Theme;

use App\Models\Theme\Theme;
use App\Repositories\Theme\ThemeRepository;
use Illuminate\Support\Facades\DB;

class PublishThemeAction
{
    public function __construct(
        private ThemeRepository $themeRepository
    ) {
    }

    public function execute(Theme $theme): Theme
    {
        return DB::transaction(function () use ($theme) {
            // Deactivate all other themes for this store
            $this->themeRepository->deactivateAllForStore($theme->store_id);
            
            // Unpublish all other themes for this store
            $this->themeRepository->unpublishAllForStore($theme->store_id);

            // Publish and activate this theme
            return $this->themeRepository->update($theme, [
                'is_published' => true,
                'is_active' => true,
                'published_at' => now(),
            ]);
        });
    }
}
