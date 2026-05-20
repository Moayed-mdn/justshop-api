<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\Cms\MarketingPage;
use Closure;
use Illuminate\Support\Facades\Cache;

class MarketingPageCacheService
{
    public function remember(string $locale, string $slug, Closure $callback): MarketingPage
    {
        /** @var MarketingPage $page */
        $page = Cache::remember(
            $this->key($locale, $slug),
            now()->addHour(),
            $callback,
        );

        return $page;
    }

    public function invalidateForPage(MarketingPage $page, array $additionalSlugs = []): void
    {
        $pageSlugs = is_array($page->slug) ? array_values($page->slug) : [];
        $locales = config('content.editable_locales', ['en', 'ar']);

        $slugs = array_values(array_unique(array_filter([
            ...$pageSlugs,
            ...$additionalSlugs,
        ])));

        foreach (is_array($locales) ? $locales : ['en', 'ar'] as $locale) {
            foreach ($slugs as $slug) {
                Cache::forget($this->key((string) $locale, (string) $slug));
            }
        }
    }

    private function key(string $locale, string $slug): string
    {
        return sprintf('cms:page:%s:%s', $locale, $slug);
    }
}
