<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Documentation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicSidebarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'title' => $this->title[$locale] ?? $this->title['en'] ?? '',
            'slug' => $this->slug[$locale] ?? $this->slug['en'] ?? '',
            'children' => PublicSidebarResource::collection($this->whenLoaded('children')),
        ];
    }
}
