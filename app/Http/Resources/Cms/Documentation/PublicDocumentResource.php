<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Documentation;

use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Http\Resources\Cms\Seo\SeoResource;
use App\Services\Cms\Seo\SeoResolutionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Services\Cms\LocalizedContentResolver;

class PublicDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var LocalizedContentResolver $resolver */
        $resolver = app(LocalizedContentResolver::class);

        $locale = app()->getLocale();
        $fallback = config('content.default_locale', 'en');

        /** @var SeoResolutionService $seoService */
        $seoService = app(SeoResolutionService::class);

        $seoArray = is_array($this->seo) ? $this->seo : [];
        $seoMeta  = SeoMetaDTO::fromArray($seoArray);
        $slugMap  = is_array($this->slug) ? $this->slug : [];

        $resolvedSeo = $seoService->resolve(
            seo: $seoMeta,
            locale: $locale,
            fallback: $fallback,
            slugMap: $slugMap,
            routePrefix: 'docs',
            isPublished: $this->is_published && ($this->published_at === null || $this->published_at->isPast()),
            entityData: [
                'updated_at' => $this->updated_at?->toAtomString(),
            ],
        );

        return [
            'id' => $this->id,
            'type' => 'documentation',
            'locale' => $locale,
            'title' => $resolver->resolveLocalizedField($this->title, $locale, $fallback),
            'slug' => $resolver->resolveLocalizedField($this->slug, $locale, $fallback),
            'content' => $resolver->resolveLocalizedField($this->content, $locale, $fallback),
            'excerpt' => $resolver->resolveLocalizedField($this->excerpt, $locale, $fallback),
            'seo' => new SeoResource($resolvedSeo),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
