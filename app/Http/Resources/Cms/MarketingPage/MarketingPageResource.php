<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\MarketingPage;

use App\Models\Cms\MarketingPage;
use App\Services\Cms\LocalizedContentResolver;
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
        $resolver = \app(LocalizedContentResolver::class);
        $locale = (string) ($request->query('locale') ?: \config('content.default_locale', 'en'));
        $fallbackLocale = (string) \config('content.default_locale', 'en');
        $seo = is_array($page->seo) ? $page->seo : [];

        return [
            'type' => $page->type->value,
            'slug' => $resolver->resolveLocalizedField($page->slug, $locale, $fallbackLocale),
            'title' => $resolver->resolveLocalizedField($page->title, $locale, $fallbackLocale),
            'sections' => $resolver->resolveLocalizedPayload($page->sections, $locale, $fallbackLocale),
            'seo' => [
                'meta_title' => $resolver->resolveLocalizedField($seo['meta_title'] ?? null, $locale, $fallbackLocale),
                'meta_description' => $resolver->resolveLocalizedField($seo['meta_description'] ?? null, $locale, $fallbackLocale),
                'canonical' => $seo['canonical_url'] ?? null,
                'robots' => $seo['robots'] ?? 'index,follow',
                'og_image' => $seo['og_image'] ?? null,
            ],
        ];
    }
}
