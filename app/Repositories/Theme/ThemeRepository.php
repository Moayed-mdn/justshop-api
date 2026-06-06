<?php

namespace App\Repositories\Theme;

use App\Models\Theme\Theme;
use Illuminate\Database\Eloquent\Collection;

class ThemeRepository
{
    /**
     * Find theme by ID
     */
    public function find(int $id): ?Theme
    {
        return Theme::find($id);
    }

    /**
     * Find theme by ID with relationships
     */
    public function findWithRelations(int $id, array $relations = ['sections.blocks', 'templates']): ?Theme
    {
        return Theme::with($relations)->find($id);
    }

    /**
     * Get all themes for a store
     */
    public function getAllForStore(int $storeId): Collection
    {
        return Theme::where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active theme for a store
     */
    public function getActiveForStore(int $storeId): ?Theme
    {
        return Theme::where('store_id', $storeId)
            ->where('is_active', true)
            ->with(['sections.blocks', 'templates'])
            ->first();
    }

    /**
     * Get published theme for a store
     */
    public function getPublishedForStore(int $storeId): ?Theme
    {
        return Theme::where('store_id', $storeId)
            ->where('is_published', true)
            ->with(['sections.blocks', 'templates'])
            ->first();
    }

    /**
     * Create a new theme
     */
    public function create(array $data): Theme
    {
        return Theme::create($data);
    }

    /**
     * Update theme
     */
    public function update(Theme $theme, array $data): Theme
    {
        $theme->update($data);
        return $theme->fresh();
    }

    /**
     * Delete theme
     */
    public function delete(Theme $theme): bool
    {
        return $theme->delete();
    }

    /**
     * Unpublish all themes for a store
     */
    public function unpublishAllForStore(int $storeId): int
    {
        return Theme::where('store_id', $storeId)
            ->update([
                'is_published' => false,
                'published_at' => null,
            ]);
    }

    /**
     * Deactivate all themes for a store
     */
    public function deactivateAllForStore(int $storeId): int
    {
        return Theme::where('store_id', $storeId)
            ->update(['is_active' => false]);
    }

    /**
     * Find theme by slug for a store
     */
    public function findBySlug(int $storeId, string $slug): ?Theme
    {
        return Theme::where('store_id', $storeId)
            ->where('slug', $slug)
            ->first();
    }
}
