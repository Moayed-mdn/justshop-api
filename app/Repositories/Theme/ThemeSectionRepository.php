<?php

namespace App\Repositories\Theme;

use App\Models\Theme\ThemeSection;
use Illuminate\Database\Eloquent\Collection;

class ThemeSectionRepository
{
    /**
     * Find section by ID
     */
    public function find(int $id): ?ThemeSection
    {
        return ThemeSection::find($id);
    }

    /**
     * Find section with blocks
     */
    public function findWithBlocks(int $id): ?ThemeSection
    {
        return ThemeSection::with('blocks')->find($id);
    }

    /**
     * Get all sections for a theme
     */
    public function getAllForTheme(int $themeId): Collection
    {
        return ThemeSection::where('theme_id', $themeId)
            ->orderBy('position')
            ->get();
    }

    /**
     * Get all sections with blocks for a theme
     */
    public function getAllWithBlocksForTheme(int $themeId): Collection
    {
        return ThemeSection::where('theme_id', $themeId)
            ->with('blocks')
            ->orderBy('position')
            ->get();
    }

    /**
     * Create a new section
     */
    public function create(array $data): ThemeSection
    {
        return ThemeSection::create($data);
    }

    /**
     * Update section
     */
    public function update(ThemeSection $section, array $data): ThemeSection
    {
        $section->update($data);
        return $section->fresh();
    }

    /**
     * Delete section
     */
    public function delete(ThemeSection $section): bool
    {
        return $section->delete();
    }

    /**
     * Reorder sections
     */
    public function reorder(array $sectionIds): void
    {
        foreach ($sectionIds as $position => $sectionId) {
            ThemeSection::where('id', $sectionId)
                ->update(['position' => $position]);
        }
    }

    /**
     * Get next position for a theme
     */
    public function getNextPosition(int $themeId): int
    {
        $maxPosition = ThemeSection::where('theme_id', $themeId)
            ->max('position');

        return ($maxPosition ?? -1) + 1;
    }

    /**
     * Find section by handle for a theme
     */
    public function findByHandle(int $themeId, string $handle): ?ThemeSection
    {
        return ThemeSection::where('theme_id', $themeId)
            ->where('handle', $handle)
            ->first();
    }
}
