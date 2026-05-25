<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\MarketingPage;

use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Http\Resources\Cms\Seo\SeoResource;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Models\Cms\MarketingPage;
use App\Services\Cms\LocalizedContentResolver;
use App\Services\Cms\Seo\SeoResolutionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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

        $isPublished = $this->isPublished($page);

        $resolvedSeo = $seoService->resolve(
            seo: $seoMeta,
            locale: $locale,
            fallback: $fallbackLocale,
            slugMap: $slugMap,
            routePrefix: '',
            isPublished: $isPublished,
        );

        return [
            'id'       => $page->id,
            'type'     => $page instanceof PlatformMarketingPage ? 'platform_marketing_page' : 'marketing_page',
            'page_type' => $this->resolvePageType($page),
            'locale'   => $locale,
            'slug'     => $resolver->resolveLocalizedField($slugMap, $locale, $fallbackLocale),
            'title'    => $resolver->resolveLocalizedField($page->title, $locale, $fallbackLocale),
            'sections' => $this->resolveSections($page, $resolver, $locale, $fallbackLocale),
            'seo'      => new SeoResource($resolvedSeo),
            'updated_at' => $page->updated_at?->toAtomString(),
        ];
    }

    private function isPublished($page): bool
    {
        if ($page instanceof PlatformMarketingPage) {
            $publishedEnum = \App\Enums\Cms\Marketing\MarketingPageStatusEnum::PUBLISHED;
        } else {
            $publishedEnum = \App\Enums\Cms\MarketingPage\MarketingPageStatusEnum::PUBLISHED;
        }

        return $page->status === $publishedEnum
            && ($page->published_at === null || $page->published_at->isPast());
    }

    private function resolvePageType($page): string
    {
        if ($page instanceof PlatformMarketingPage) {
            return $page->template?->value ?? 'generic';
        }

        return $page->type->value;
    }

    private function resolveSections($page, LocalizedContentResolver $resolver, string $locale, string $fallback): mixed
    {
        if ($page instanceof PlatformMarketingPage) {
            if (!empty($page->content)) {
                return $resolver->resolveLocalizedPayload($page->content, $locale, $fallback);
            }

            $sections = $page->sections->map(fn ($section) => [
                'type' => $section->section_type,
                'title' => $section->title,
                'subtitle' => $section->subtitle,
                'content' => $section->content,
                'settings' => $section->settings,
            ])->toArray();

            return $resolver->resolveLocalizedPayload($sections, $locale, $fallback);
        }

        return $resolver->resolveLocalizedPayload($page->sections, $locale, $fallback);
    }
}
