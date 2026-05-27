<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\Cms\MarketingPage;
use App\Services\Cms\Seo\SitemapService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Cache service for marketing page content.
 *
 * Decoupled from the legacy MarketingPage model:
 * - remember()          still returns MarketingPage for backward-compat with legacy public controller
 * - invalidateForPage() now accepts any Eloquent model that has slug + type attributes
 * - invalidateForSlugMap() is the new model-agnostic invalidation path used by platform/store actions
 * - invalidateAll()     unchanged — flushes the full cms:marketing tag
 */
class MarketingPageCacheService
{
    private const TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly SitemapService $sitemapService,
    ) {}

    // ── Legacy path (used by legacy PublicMarketingController) ────────────

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

    /**
     * Legacy invalidation — accepts the legacy MarketingPage model.
     * Kept for backward compatibility with legacy actions.
     */
    public function invalidateForPage(MarketingPage $page, array $additionalSlugs = []): void
    {
        Cache::tags($this->pageTypeTags($page))->flush();

        $pageSlugs = is_array($page->slug) ? array_values($page->slug) : [];
        $slugs     = array_values(array_unique(array_filter([...$pageSlugs, ...$additionalSlugs])));

        $this->flushSlugKeys($slugs);
        $this->sitemapService->invalidateMarketing();
    }

    // ── Model-agnostic path (used by platform + store actions) ────────────

    /**
     * Invalidate cache entries by slug map.
     * Works with any model — no type dependency.
     *
     * @param array<string, string> $slugMap  e.g. ['en' => 'summer-sale', 'ar' => 'تخفيضات-الصيف']
     * @param array<string>         $additionalSlugs  extra scalar slugs to also flush
     */
    public function invalidateForSlugMap(array $slugMap, array $additionalSlugs = []): void
    {
        $slugs = array_values(array_unique(array_filter([
            ...array_values($slugMap),
            ...$additionalSlugs,
        ])));

        $this->flushSlugKeys($slugs);
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

    // ── Internals ─────────────────────────────────────────────────────────

    private function flushSlugKeys(array $slugs): void
    {
        $locales = config('content.editable_locales', ['en', 'ar']);

        foreach (is_array($locales) ? $locales : ['en', 'ar'] as $locale) {
            foreach ($slugs as $slug) {
                Cache::tags($this->pageTags())
                    ->forget($this->key((string) $locale, (string) $slug));
            }
        }
    }

    private function key(string $locale, string $slug): string
    {
        return sprintf('cms:page:%s:%s', $locale, $slug);
    }

    /** @return string[] */
    private function pageTags(): array
    {
        return ['cms:marketing'];
    }

    /**
     * Legacy type-tagged invalidation — only valid for the legacy MarketingPage model
     * which carries a `type` enum attribute.
     *
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
