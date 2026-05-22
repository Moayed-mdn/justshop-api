<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\Cms\MarketingPage;
use App\Services\Cms\Seo\SitemapService;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache service for marketing page content.
 */
class MarketingPageCacheService
{
    private const TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly SitemapService $sitemapService,
    ) {}

    public function remember(string $locale, string $slug, Closure $callback): MarketingPage
    {
        /** @var MarketingPage $page */
        $page = Cache::tags($this->pageTags())
            ->remember(
                $this->key($locale, $slug),
                self::TTL_SECONDS,
                $callback,
            );

        return $page;
    }

    public function invalidateForPage(MarketingPage $page, array $additionalSlugs = []): void
    {
        // Flush all entries tagged with this page type
        Cache::tags($this->pageTypeTags($page))->flush();

        // Also flush by key for each locale+slug combination
        $pageSlugs = is_array($page->slug) ? array_values($page->slug) : [];
        $locales   = config('content.editable_locales', ['en', 'ar']);

        $slugs = array_values(array_unique(array_filter([
            ...$pageSlugs,
            ...$additionalSlugs,
        ])));

        foreach (is_array($locales) ? $locales : ['en', 'ar'] as $locale) {
            foreach ($slugs as $slug) {
                Cache::tags($this->pageTags())
                    ->forget($this->key((string) $locale, (string) $slug));
            }
        }

        // Publishing always changes sitemap
        $this->sitemapService->invalidateMarketing();
    }

    /**
     * Flush all marketing page cache entries.
     */
    public function invalidateAll(): void
    {
        Cache::tags($this->pageTags())->flush();
        $this->sitemapService->invalidateMarketing();
    }

    private function key(string $locale, string $slug): string
    {
        return sprintf('cms:page:%s:%s', $locale, $slug);
    }

    /**
     * @return string[]
     */
    private function pageTags(): array
    {
        return ['cms:marketing'];
    }

    /**
     * @return string[]
     */
    private function pageTypeTags(MarketingPage $page): array
    {
        return [
            'cms:marketing',
            'cms:marketing:' . $page->type->value,
        ];
    }
}
