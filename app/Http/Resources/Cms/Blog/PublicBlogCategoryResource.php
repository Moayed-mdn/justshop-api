<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Blog;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicBlogCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = app()->getLocale();
        $fallback = config('content.default_locale', 'en');

        return [
            'id' => $this->id,
            'name' => $this->name[$locale] ?? $this->name[$fallback] ?? '',
            'slug' => $this->slug[$locale] ?? $this->slug[$fallback] ?? '',
        ];
    }
}
