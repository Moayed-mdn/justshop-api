<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Blog;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicBlogCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = $request->query('locale', config('content.default_locale', 'en'));
        $translation = $this->translation($locale);

        return [
            'id' => $this->id,
            'name' => $translation?->name,
            'slug' => $translation?->slug,
        ];
    }
}
