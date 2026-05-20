<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Blog;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicBlogPostResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = $request->query('locale', config('content.default_locale', 'en'));
        $translation = $this->translation($locale);
        $isShowRoute = $request->route()?->getName() === 'public.blog.show';

        return [
            'id' => $this->id,
            'locale' => $translation?->locale ?? $locale,
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'excerpt' => $translation?->excerpt,
            'cover_image' => $this->cover_image,
            'reading_time' => $this->reading_time,
            'featured' => $this->featured,
            'published_at' => $this->published_at,
            'category' => $this->whenLoaded('category', fn () => $this->category ? new PublicBlogCategoryResource($this->category) : null),
            'tags' => $this->relationLoaded('tags') ? PublicBlogTagResource::collection($this->tags) : [],
            'author' => $this->whenLoaded('author', fn () => $this->author ? new BlogAuthorResource($this->author) : null),
            'seo' => [
                'title' => $translation?->meta_title ?? $translation?->title,
                'description' => $translation?->meta_description ?? $translation?->excerpt,
                'canonical' => $translation?->canonical_url,
                'og_image' => $translation?->og_image,
                'robots' => $translation?->robots ?? 'index,follow',
            ],
            'content' => $this->when($isShowRoute, fn () => $translation?->content),
        ];
    }
}
