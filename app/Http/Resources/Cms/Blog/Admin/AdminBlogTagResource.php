<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Blog\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminBlogTagResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'translations' => $this->relationLoaded('translations')
                ? $this->translations
                    ->keyBy('locale')
                    ->map(fn ($translation) => [
                        'name' => $translation->name,
                        'slug' => $translation->slug,
                    ])
                    ->toArray()
                : [],
        ];
    }
}
