<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Documentation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'title' => $this->title[$locale] ?? $this->title['en'] ?? '',
            'slug' => $this->slug[$locale] ?? $this->slug['en'] ?? '',
            'content' => $this->content[$locale] ?? $this->content['en'] ?? '',
            'excerpt' => $this->excerpt[$locale] ?? $this->excerpt['en'] ?? '',
            'seo' => [
                'title' => $this->meta_title[$locale] ?? $this->meta_title['en'] ?? null,
                'description' => $this->meta_description[$locale] ?? $this->meta_description['en'] ?? null,
                'canonical_url' => $this->canonical_url[$locale] ?? $this->canonical_url['en'] ?? null,
                'og_image' => $this->og_image[$locale] ?? $this->og_image['en'] ?? null,
                'robots' => $this->robots[$locale] ?? $this->robots['en'] ?? null,
                'index_controls' => $this->index_controls[$locale] ?? $this->index_controls['en'] ?? null,
            ],
            'updated_at' => $this->updated_at,
        ];
    }
}
