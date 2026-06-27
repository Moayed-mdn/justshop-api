<?php

declare(strict_types=1);

namespace App\Http\Resources\Navigation;

use Illuminate\Http\Resources\Json\JsonResource;

class NavigationMenuResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description,
            'settings' => $this->settings,
            'is_active' => $this->is_active,
            'items_count' => (int) $this->items_count,
            'items' => NavigationMenuItemResource::collection($this->whenLoaded('rootItems')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
