<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Cms\MarketingPage;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminMarketingPageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'slug' => $this->slug,
            'title' => $this->title,
            'sections' => $this->sections,
            'seo' => $this->seo,
            'status' => $this->status->value,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'updater' => $this->whenLoaded('updater', fn () => $this->updater ? [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
            ] : null),
        ];
    }
}
