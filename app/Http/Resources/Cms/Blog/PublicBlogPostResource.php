<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Blog;

use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Http\Resources\Cms\Seo\SeoResource;
use App\Services\Cms\LocalizedContentResolver;
use App\Services\Cms\Seo\SeoResolutionService;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicBlogPostResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var LocalizedContentResolver $resolver */
        $resolver = app(LocalizedContentResolver::class);

        $locale = app()->getLocale();
        $fallback = config('content.default_locale', 'en');
        $isShowRoute = $request->route()?->getName() === 'public.cms.blog.show';

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
            routePrefix: 'blog',
            isPublished: $this->is_published && ($this->published_at === null || $this->published_at->isPast()),
            entityData: [
                'author_name' => $this->author?->name,
                'cover_image' => $this->cover_image,
                'published_at' => $this->published_at?->toAtomString(),
                'updated_at' => $this->updated_at?->toAtomString(),
            ],
        );

        return [
            'id' => $this->id,
            'type' => 'blog_post',
            'locale' => $locale,
            'title' => $resolver->resolveLocalizedField($this->title, $locale, $fallback),
            'slug' => $resolver->resolveLocalizedField($this->slug, $locale, $fallback),
            'excerpt' => $resolver->resolveLocalizedField($this->excerpt, $locale, $fallback),
            'content' => $this->when($isShowRoute, fn () => $resolver->resolveLocalizedField($this->content, $locale, $fallback)),
            'cover_image' => $this->cover_image,
            'reading_time' => $this->reading_time,
            'featured' => $this->featured,
            'published_at' => $this->published_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
            'category' => $this->whenLoaded('category', fn () => $this->category ? new PublicBlogCategoryResource($this->category) : null),
            'tags' => $this->relationLoaded('tags') ? PublicBlogTagResource::collection($this->tags) : [],
            'author' => $this->whenLoaded('author', fn () => $this->author ? new BlogAuthorResource($this->author) : null),
            'seo' => new SeoResource($resolvedSeo),
        ];
    }
}
