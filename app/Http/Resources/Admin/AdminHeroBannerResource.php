<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminHeroBannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'cat_url' => $this->cat_url,
            'position' => $this->position,
            'visual_type' => $this->visual_type?->value,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'gradient_from' => $this->gradient_from,
            'gradient_to' => $this->gradient_to,
            'video_url' => $this->video_url,
            'link_url' => $this->link_url,
            'link_text' => $this->link_text,
            'link_target' => $this->link_target?->value,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'translations' => $this->translations->map(fn ($translation) => [
                'locale' => $translation->locale,
                'title' => $translation->title,
                'subtitle' => $translation->subtitle,
                'cta_text' => $translation->cta_text,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
