<?php

declare(strict_types=1);

namespace App\Services\Cms\Seo;

use App\DTOs\Cms\Seo\ResolvedSeoDTO;
use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Enums\Cms\Seo\RobotsDirectiveEnum;
use App\Services\Cms\LocalizedContentResolver;

/**
 * Resolves a SeoMetaDTO into a locale-specific ResolvedSeoDTO.
 *
 * Responsibilities:
 * - Resolve localized fields (meta_title, meta_description, og_image) to scalar
 * - Generate hreflang alternates from slug map + FRONTEND_URL
 * - Apply environment-aware robots override (staging = noindex)
 * - Apply draft content robots override
 * - Merge og fields from meta fields when og fields are absent
 *
 * This service does NOT touch the database.
 */
final class SeoResolutionService
{
    public function __construct(
        private readonly CanonicalUrlService $canonicalUrlService,
        private readonly StructuredDataService $structuredDataService,
        private readonly LocalizedContentResolver $resolver,
    ) {}

    /**
     * Resolve SEO metadata for API response.
     *
     * @param SeoMetaDTO          $seo          Raw SEO metadata from storage
     * @param string              $locale        Requested locale
     * @param string              $fallback      Fallback locale
     * @param array<string,mixed> $slugMap       Localized slug map for alternates generation
     * @param string              $routePrefix   e.g. "" for root pages, "blog" for blog posts
     * @param bool                $isPublished   Whether content is published
     * @param array<string,mixed> $entityData    Optional extra data for structured data generation
     */
    public function resolve(
        SeoMetaDTO $seo,
        string $locale,
        string $fallback,
        array $slugMap,
        string $routePrefix = '',
        bool $isPublished = true,
        array $entityData = [],
    ): ResolvedSeoDTO {
        // Draft content must NEVER be indexable
        $robots = $isPublished
            ? $this->applyEnvironmentRobots($seo->robots)
            : RobotsDirectiveEnum::forDraft();

        $metaTitle       = $this->resolver->resolveLocalizedField($seo->metaTitle, $locale, $fallback);
        $metaDescription = $this->resolver->resolveLocalizedField($seo->metaDescription, $locale, $fallback);

        // OG falls back to meta fields
        $ogTitle = $this->resolver->resolveLocalizedField(
            $seo->ogTitle ?: $seo->metaTitle,
            $locale,
            $fallback,
        );
        $ogDescription = $this->resolver->resolveLocalizedField(
            $seo->ogDescription ?: $seo->metaDescription,
            $locale,
            $fallback,
        );

        // OG image: may be locale map or scalar
        $ogImage = $this->resolver->resolveLocalizedField($seo->ogImage, $locale, $fallback);

        // Canonical: prefer explicit, else generate from primary slug
        $canonical = $seo->canonicalUrl
            ?? $this->canonicalUrlService->generateCanonical($slugMap, $fallback, $routePrefix);

        // Alternates for hreflang
        $alternates = $this->canonicalUrlService->generateAlternates($slugMap, $routePrefix);

        // Generate base structured data if not explicitly provided
        $structuredData = $seo->structuredData 
            ? $this->resolver->resolveLocalizedPayload($seo->structuredData, $locale, $fallback)
            : $this->generateDefaultStructuredData($routePrefix, array_merge($entityData, [
                'title' => $metaTitle,
                'excerpt' => $metaDescription,
                'url' => $canonical,
            ]));

        return new ResolvedSeoDTO(
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
            canonicalUrl: $canonical,
            ogImage: $ogImage,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            robots: $robots->value,
            isIndexable: $robots->isIndexable(),
            isFollowable: $robots->isFollowable(),
            twitterCard: $seo->twitterCard,
            alternates: $alternates,
            structuredData: $structuredData,
        );
    }

    private function generateDefaultStructuredData(string $routePrefix, array $data): ?array
    {
        return match ($routePrefix) {
            'blog' => $this->structuredDataService->article($data),
            'docs' => $this->structuredDataService->techArticle($data),
            ''     => $this->structuredDataService->website(),
            default => null,
        };
    }

    /**
     * Staging/preview environments must block indexing entirely.
     */
    private function applyEnvironmentRobots(RobotsDirectiveEnum $robots): RobotsDirectiveEnum
    {
        $env = config('app.env');

        if (in_array($env, ['staging', 'testing', 'local'], true)) {
            return RobotsDirectiveEnum::NOINDEX_NOFOLLOW;
        }

        return $robots;
    }
}
