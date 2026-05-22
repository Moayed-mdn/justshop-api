<?php

declare(strict_types=1);

namespace App\Repositories\Cms\Sitemap;

use App\Models\BlogPost;
use App\Models\Cms\MarketingPage;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for sitemap data retrieval.
 *
 * Rules:
 * - Published content only (never draft or scheduled future)
 * - Minimal column selection for performance
 * - Eager load only what sitemap needs
 *
 * This is intentionally separate from content repositories
 * to keep sitemap queries lean and independent of pagination.
 */
final class SitemapRepository
{
    /**
     * Get all published marketing pages for sitemap.
     * Minimal columns only — no sections, no seo blob.
     */
    public function getPublishedMarketingPages(): Collection
    {
        return MarketingPage::query()
            ->select(['id', 'type', 'slug', 'status', 'published_at', 'updated_at'])
            ->published()
            ->orderBy('type')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get all published blog posts for sitemap.
     */
    public function getPublishedBlogPosts(): Collection
    {
        return BlogPost::query()
            ->select(['id', 'slug', 'is_published', 'published_at', 'updated_at'])
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Get all published documentation for sitemap.
     */
    public function getPublishedDocs(): Collection
    {
        return \App\Models\Cms\CmsDocument::query()
            ->select(['id', 'slug', 'is_published', 'published_at', 'updated_at'])
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
