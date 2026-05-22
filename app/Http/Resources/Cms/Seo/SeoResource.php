<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Seo;

use App\DTOs\Cms\Seo\ResolvedSeoDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes the resolved SEO payload for API consumers (Next.js).
 *
 * Input: ResolvedSeoDTO (all scalar — no locale maps)
 * Output: Flat JSON object ready for generateMetadata()
 *
 * Contract:
 * {
 *   "meta_title": "...",
 *   "meta_description": "...",
 *   "canonical_url": "https://...",
 *   "og": {
 *     "title": "...",
 *     "description": "...",
 *     "image": "..."
 *   },
 *   "twitter": {
 *     "card": "summary_large_image"
 *   },
 *   "robots": "index,follow",
 *   "is_indexable": true,
 *   "is_followable": true,
 *   "alternates": {
 *     "en": "https://example.com/about",
 *     "ar": "https://example.com/ar/من-نحن",
 *     "x-default": "https://example.com/about"
 *   },
 *   "structured_data": null
 * }
 *
 * @mixin ResolvedSeoDTO
 */
class SeoResource extends JsonResource
{
    /**
     * @param ResolvedSeoDTO $resource
     */
    public function __construct(ResolvedSeoDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var ResolvedSeoDTO $seo */
        $seo = $this->resource;

        return [
            'meta_title'       => $seo->metaTitle,
            'meta_description' => $seo->metaDescription,
            'canonical_url'    => $seo->canonicalUrl,
            'alternates'       => (object) ($seo->alternates ?: []),
            'robots' => [
                'index'  => $seo->isIndexable,
                'follow' => $seo->isFollowable,
                'all'    => $seo->robots,
            ],
            'og' => [
                'title'       => $seo->ogTitle ?? $seo->metaTitle,
                'description' => $seo->ogDescription ?? $seo->metaDescription,
                'image'       => $seo->ogImage,
                'type'        => 'website',
            ],
            'twitter' => [
                'card'        => $seo->twitterCard,
                'title'       => $seo->ogTitle ?? $seo->metaTitle,
                'description' => $seo->ogDescription ?? $seo->metaDescription,
                'image'       => $seo->ogImage,
            ],
            'structured_data' => (object) ($seo->structuredData ?: []),
        ];
    }
}
