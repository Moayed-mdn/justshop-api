<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\MarketingPage;

use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Http\Resources\Cms\Seo\SeoResource;
use App\Models\Cms\MarketingPage;
use App\Services\Cms\LocalizedContentResolver;
use App\Services\Cms\Seo\SeoResolutionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MarketingPage */
class MarketingPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MarketingPage $page */
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

        $isPublished = $page->status === \App\Enums\Cms\MarketingPage\MarketingPageStatusEnum::PUBLISHED
            && ($page->published_at === null || $page->published_at->isPast());

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
            'type'     => 'marketing_page',
            'page_type' => $page->type->value,
            'locale'   => $locale,
            'slug'     => $resolver->resolveLocalizedField($slugMap, $locale, $fallbackLocale),
            'title'    => $resolver->resolveLocalizedField($page->title, $locale, $fallbackLocale),
            'sections' => $resolver->resolveLocalizedPayload($page->sections, $locale, $fallbackLocale),
            'seo'      => new SeoResource($resolvedSeo),
            'updated_at' => $page->updated_at?->toAtomString(),
        ];
    }
}
