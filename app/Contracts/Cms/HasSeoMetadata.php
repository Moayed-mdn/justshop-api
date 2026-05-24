<?php

declare(strict_types=1);

namespace App\Contracts\Cms;

use App\DTOs\Cms\Seo\SeoMetaDTO;

/**
 * Contract for CMS entities that support SEO metadata.
 *
 * All CMS content types (Marketing Pages, Blog Posts, Documentation)
 * implement this interface to ensure consistent SEO handling.
 */
interface HasSeoMetadata
{
    /**
     * Get the SEO metadata for this entity.
     */
    public function getSeoMetadata(): SeoMetaDTO;

    /**
     * Get the localized slug map for alternate URL generation.
     *
     * @return array<string, string>
     */
    public function getSlugMap(): array;

    /**
     * Get the route prefix for URL generation.
     * Examples: '' (root), 'blog', 'docs'
     */
    public function getRoutePrefix(): string;

    /**
     * Check if the entity is published and indexable.
     */
    public function isPublished(): bool;
}
