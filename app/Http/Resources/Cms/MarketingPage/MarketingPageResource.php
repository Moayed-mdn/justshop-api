<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\MarketingPage;

use App\Contracts\Cms\HasSeoMetadata;
use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Http\Resources\Cms\Seo\SeoResource;
use App\Services\Cms\LocalizedContentResolver;
use App\Services\Cms\Seo\SeoResolutionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-facing marketing page resource.
 *
 * Supports both PlatformMarketingPage (new) and legacy MarketingPage.
 * The instanceof branching has been replaced with duck-typed attribute
 * access so this resource does not need to import concrete model classes.
 *
 * Resolution priority:
 *   1. content column (new platform / store pages)
 *   2. sections relation (new platform pages with separate section rows)
 *   3. sections column (legacy marketing_pages JSON blob)
 */
class MarketingPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $page = $this->resource;

        /** @var LocalizedContentResolver $resolver */
        $resolver = app(LocalizedContentResolver::class);

        /** @var SeoResolutionService $seoService */
        $seoService = app(SeoResolutionService::class);

        $locale         = app()->getLocale();
        $fallbackLocale = (string) config('content.default_locale', 'en');

        $seoArray = is_array($page->seo) ? $page->seo : [];
        $seoMeta  = SeoMetaDTO::fromArray($seoArray);
        $slugMap  = is_array($page->slug) ? $page->slug : [];

        $isPublished = $page instanceof HasSeoMetadata
            ? $page->isPublished()
            : $this->legacyIsPublished($page);

        $resolvedSeo = $seoService->resolve(
            seo: $seoMeta,
            locale: $locale,
            fallback: $fallbackLocale,
            slugMap: $slugMap,
            routePrefix: '',
            isPublished: $isPublished,
        );

        return [
            'id'        => $page->id,
            'type'      => $this->resolveType($page),
            'page_type' => $this->resolvePageType($page),
            'locale'    => $locale,
            'slug'      => $resolver->resolveLocalizedField($slugMap, $locale, $fallbackLocale),
            'title'     => $resolver->resolveLocalizedField($page->title, $locale, $fallbackLocale),
            'sections'  => $this->resolveSections($page, $resolver, $locale, $fallbackLocale),
            'seo'       => new SeoResource($resolvedSeo),
            'updated_at' => $page->updated_at?->toAtomString(),
        ];
    }

    // ── Type resolution ───────────────────────────────────────────────────

    private function resolveType(mixed $page): string
    {
        // New models carry a template attribute; legacy carries a type attribute.
        if (isset($page->template)) {
            return 'platform_marketing_page';
        }

        return 'marketing_page';
    }

    private function resolvePageType(mixed $page): string
    {
        // New platform/store pages: template enum
        if (isset($page->template) && $page->template !== null) {
            return $page->template instanceof \BackedEnum
                ? $page->template->value
                : (string) $page->template;
        }

        // Legacy pages: type enum
        if (isset($page->type) && $page->type !== null) {
            return $page->type instanceof \BackedEnum
                ? $page->type->value
                : (string) $page->type;
        }

        return 'generic';
    }

    // ── Section resolution ────────────────────────────────────────────────

    private function resolveSections(
        mixed $page,
        LocalizedContentResolver $resolver,
        string $locale,
        string $fallback,
    ): mixed {
        // Priority 1: content column (new pages — platform or store)
        if (!empty($page->content) && is_array($page->content)) {
            return $resolver->resolveLocalizedPayload($page->content, $locale, $fallback);
        }

        // Priority 2: sections relation (new platform pages with separate rows)
        if ($page->relationLoaded('sections') && $page->sections->isNotEmpty()) {
            $sections = $page->sections->map(fn ($section) => [
                'type'     => $section->section_type instanceof \BackedEnum
                    ? $section->section_type->value
                    : $section->section_type,
                'title'    => $section->title,
                'subtitle' => $section->subtitle,
                'content'  => $section->content,
                'settings' => $section->settings,
            ])->toArray();

            return $resolver->resolveLocalizedPayload($sections, $locale, $fallback);
        }

        // Priority 3: legacy sections JSON column
        if (isset($page->sections) && is_array($page->sections)) {
            return $resolver->resolveLocalizedPayload($page->sections, $locale, $fallback);
        }

        return null;
    }

    // ── Legacy helpers ────────────────────────────────────────────────────

    /**
     * Fallback isPublished check for the legacy MarketingPage model which
     * uses a different status enum namespace.
     */
    private function legacyIsPublished(mixed $page): bool
    {
        $publishedValue = \App\Enums\Cms\MarketingPage\MarketingPageStatusEnum::PUBLISHED;

        return $page->status === $publishedValue
            && ($page->published_at === null || $page->published_at->isPast());
    }
}
