<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Seo;

use App\Enums\Cms\Seo\RobotsDirectiveEnum;

/**
 * Locale-resolved SEO payload for API responses.
 *
 * All fields are scalar — no locale maps.
 * This is what the frontend (Next.js) receives and uses directly
 * in generateMetadata() without any client-side resolution.
 */
final class ResolvedSeoDTO
{
    public function __construct(
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly ?string $canonicalUrl,
        public readonly ?string $ogImage,
        public readonly ?string $ogTitle,
        public readonly ?string $ogDescription,
        public readonly string  $robots,
        public readonly bool    $isIndexable,
        public readonly bool    $isFollowable,
        public readonly string  $twitterCard,

        /**
         * hreflang alternates for <link rel="alternate" hreflang="...">
         * @var array<string, string> e.g. ["en" => "https://...", "ar" => "https://..."]
         */
        public readonly array $alternates,

        /**
         * Structured data — frontend renders as JSON-LD.
         * @var array<string, mixed>|null
         */
        public readonly ?array $structuredData,
    ) {}

    public function toArray(): array
    {
        return [
            'meta_title'       => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'canonical_url'    => $this->canonicalUrl,
            'og_image'         => $this->ogImage,
            'og_title'         => $this->ogTitle,
            'og_description'   => $this->ogDescription,
            'robots'           => $this->robots,
            'is_indexable'     => $this->isIndexable,
            'is_followable'    => $this->isFollowable,
            'twitter_card'     => $this->twitterCard,
            'alternates'       => $this->alternates,
            'structured_data'  => $this->structuredData,
        ];
    }
}
