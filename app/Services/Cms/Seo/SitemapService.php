<?php

declare(strict_types=1);

namespace App\Services\Cms\Seo;

use App\DTOs\Cms\Sitemap\SitemapEntryDTO;
use App\Enums\Cms\Seo\SitemapChangefreqEnum;
use App\Repositories\Cms\Sitemap\SitemapRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Builds sitemap entries for all CMS content types.
 */
class SitemapService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly SitemapRepository  $sitemapRepository,
        private readonly CanonicalUrlService $canonicalUrlService,
    ) {}

    /**
     * Get marketing pages sitemap entries.
     *
     * @return SitemapEntryDTO[]
     */
    public function getMarketingEntries(): array
    {
        return Cache::tags(['cms:sitemap', 'cms:sitemap:marketing'])
            ->remember(
                'sitemap:marketing',
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->buildMarketingEntries(),
            );
    }

    /**
     * Get blog posts sitemap entries.
     *
     * @return SitemapEntryDTO[]
     */
    public function getBlogEntries(): array
    {
        return Cache::tags(['cms:sitemap', 'cms:sitemap:blog'])
            ->remember(
                'sitemap:blog',
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->buildBlogEntries(),
            );
    }

    /**
     * Get documentation sitemap entries.
     *
     * @return SitemapEntryDTO[]
     */
    public function getDocsEntries(): array
    {
        return Cache::tags(['cms:sitemap', 'cms:sitemap:docs'])
            ->remember(
                'sitemap:docs',
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->buildDocsEntries(),
            );
    }

    /**
     * Invalidate ALL sitemap caches.
     * Called on any CMS publish/unpublish action.
     */
    public function invalidateAll(): void
    {
        Cache::tags(['cms:sitemap'])->flush();
    }

    /**
     * Invalidate marketing sitemap only.
     */
    public function invalidateMarketing(): void
    {
        Cache::tags(['cms:sitemap:marketing'])->flush();
    }

    /**
     * Invalidate blog sitemap only.
     */
    public function invalidateBlog(): void
    {
        Cache::tags(['cms:sitemap:blog'])->flush();
    }

    /**
     * Invalidate docs sitemap only.
     */
    public function invalidateDocs(): void
    {
        Cache::tags(['cms:sitemap:docs'])->flush();
    }

    /**
     * @return SitemapEntryDTO[]
     */
    private function buildMarketingEntries(): array
    {
        $pages = $this->sitemapRepository->getPublishedMarketingPages();
        $entries = [];

        foreach ($pages as $page) {
            $slugMap = is_array($page->slug) ? $page->slug : [];
            $canonical  = $this->canonicalUrlService->generateCanonical($slugMap);
            $alternates = $this->canonicalUrlService->generateAlternates($slugMap);

            if ($canonical === null) {
                continue;
            }

            // Home page gets highest priority
            $priority = $page->type->value === 'home' ? 1.0 : 0.8;

            $entries[] = new SitemapEntryDTO(
                loc: $canonical,
                lastmod: $page->updated_at,
                changefreq: SitemapChangefreqEnum::MONTHLY,
                priority: $priority,
                alternates: $alternates,
            );
        }

        return $entries;
    }

    /**
     * @return SitemapEntryDTO[]
     */
    private function buildBlogEntries(): array
    {
        $posts = $this->sitemapRepository->getPublishedBlogPosts();
        $entries = [];

        foreach ($posts as $post) {
            $slugMap = is_array($post->slug) ? $post->slug : [];

            if (empty($slugMap)) {
                continue;
            }

            $canonical  = $this->canonicalUrlService->generateCanonical($slugMap, 'en', 'blog');
            $alternates = $this->canonicalUrlService->generateAlternates($slugMap, 'blog');

            if ($canonical === null) {
                continue;
            }

            $entries[] = new SitemapEntryDTO(
                loc: $canonical,
                lastmod: $post->updated_at,
                changefreq: SitemapChangefreqEnum::WEEKLY,
                priority: 0.7,
                alternates: $alternates,
            );
        }

        return $entries;
    }

    /**
     * @return SitemapEntryDTO[]
     */
    private function buildDocsEntries(): array
    {
        $docs = $this->sitemapRepository->getPublishedDocs();
        $entries = [];

        foreach ($docs as $doc) {
            $slugMap = is_array($doc->slug) ? $doc->slug : [];

            if (empty($slugMap)) {
                continue;
            }

            $canonical  = $this->canonicalUrlService->generateCanonical($slugMap, 'en', 'docs');
            $alternates = $this->canonicalUrlService->generateAlternates($slugMap, 'docs');

            if ($canonical === null) {
                continue;
            }

            $entries[] = new SitemapEntryDTO(
                loc: $canonical,
                lastmod: $doc->updated_at,
                changefreq: SitemapChangefreqEnum::MONTHLY,
                priority: 0.6,
                alternates: $alternates,
            );
        }

        return $entries;
    }
}
