<?php

declare(strict_types=1);

namespace App\Http\Resources\Navigation;

use Illuminate\Http\Resources\Json\JsonResource;

class NavigationMenuItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'menu_id' => $this->menu_id,
            'parent_id' => $this->parent_id,
            'label' => $this->label,
            'type' => $this->type,
            'url' => $this->url,
            'resource_id' => $this->resource_id,
            'resource_type' => $this->resource_type,
            'target' => $this->target,
            'settings' => $this->settings,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'children' => NavigationMenuItemResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
