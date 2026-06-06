<?php

namespace App\Actions\Theme;

use App\Models\Theme\Theme;
use App\Repositories\Theme\ThemeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateThemeAction
{
    public function __construct(
        private ThemeRepository $themeRepository
    ) {
    }

    public function execute(Theme $theme, ?string $newName = null): Theme
    {
        return DB::transaction(function () use ($theme, $newName) {
            // Create new theme
            $newTheme = $theme->replicate();
            $newTheme->name = $newName ?? "{$theme->name} (Copy)";
            $newTheme->slug = Str::slug($newTheme->name);
            $newTheme->is_active = false;
            $newTheme->is_published = false;
            $newTheme->published_at = null;
            $newTheme->save();

            // Duplicate sections
            foreach ($theme->sections as $section) {
                $newSection = $section->replicate();
                $newSection->theme_id = $newTheme->id;
                $newSection->save();

                // Duplicate blocks for each section
                foreach ($section->blocks as $block) {
                    $newBlock = $block->replicate();
                    $newBlock->section_id = $newSection->id;
                    $newBlock->save();
                }
            }

            // Duplicate templates
            foreach ($theme->templates as $template) {
                $newTemplate = $template->replicate();
                $newTemplate->theme_id = $newTheme->id;
                $newTemplate->save();
            }

            return $newTheme->load(['sections.blocks', 'templates']);
        });
    }
}
