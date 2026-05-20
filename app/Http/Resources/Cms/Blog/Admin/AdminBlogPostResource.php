<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Blog\Admin;

use App\Http\Resources\Cms\Blog\BlogAuthorResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBlogPostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'author_id' => $this->author_id,
            'blog_category_id' => $this->blog_category_id,
            'featured' => $this->featured,
            'is_published' => $this->is_published,
            'publish_state' => $this->publish_state->value,
            'published_at' => $this->published_at,
            'cover_image' => $this->cover_image,
            'reading_time' => $this->reading_time,
            'category' => $this->whenLoaded('category', fn () => $this->category ? new AdminBlogCategoryResource($this->category) : null),
            'tags' => $this->relationLoaded('tags') ? AdminBlogTagResource::collection($this->tags) : [],
            'author' => $this->whenLoaded('author', fn () => $this->author ? new BlogAuthorResource($this->author) : null),
            'translations' => $this->relationLoaded('translations')
                ? $this->translations
                    ->keyBy('locale')
                    ->map(fn ($translation) => [
                        'title' => $translation->title,
                        'slug' => $translation->slug,
                        'excerpt' => $translation->excerpt,
                        'content' => $translation->content,
                        'meta_title' => $translation->meta_title,
                        'meta_description' => $translation->meta_description,
                        'canonical_url' => $translation->canonical_url,
                        'og_image' => $translation->og_image,
                        'robots' => $translation->robots,
                    ])
                    ->toArray()
                : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? ['id' => $this->creator->id, 'name' => $this->creator->name] : null),
            'updater' => $this->whenLoaded('updater', fn () => $this->updater ? ['id' => $this->updater->id, 'name' => $this->updater->name] : null),
        ];
    }
}
