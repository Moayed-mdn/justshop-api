<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Theme\Theme;
use App\Services\Storefront\Runtime\RuntimeCacheService;

class ThemeObserver
{
    public function __construct(
        private readonly RuntimeCacheService $cacheService,
    ) {}

    /**
     * Handle the Theme "updated" event.
     * Clear the storefront runtime cache when theme is updated.
     */
    public function updated(Theme $theme): void
    {
        $this->clearThemeCache($theme);
    }

    /**
     * Handle the Theme "deleted" event.
     * Clear the storefront runtime cache when theme is deleted.
     */
    public function deleted(Theme $theme): void
    {
        $this->clearThemeCache($theme);
    }

    /**
     * Clear all runtime caches for the store associated with this theme
     */
    private function clearThemeCache(Theme $theme): void
    {
        if (!$theme->store) {
            return;
        }

        // Invalidate theme artifacts for this store
        $invalidated = $this->cacheService->invalidateTenantArtifacts(
            $theme->store,
            ['theme']
        );

        logger()->info('Theme cache cleared via observer', [
            'theme_id' => $theme->id,
            'store_id' => $theme->store_id,
            'store_slug' => $theme->store->slug,
            'theme_name' => $theme->name,
            'invalidated_count' => $invalidated,
        ]);
    }
}
