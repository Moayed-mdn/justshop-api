<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Cms\Marketing\Platform;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminPlatformMarketingPageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'status' => $this->status->value,
            'published_at' => $this->published_at,
            'seo' => $this->seo,
            'template' => $this->template?->value,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'updater' => $this->whenLoaded('updater', fn () => $this->updater ? [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
            ] : null),
            'sections' => $this->whenLoaded('sections'),
        ];
    }
}
