<?php

declare(strict_types=1);

namespace App\Http\Resources\Theme;

use Illuminate\Http\Resources\Json\JsonResource;

class ThemeSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'theme_id' => $this->theme_id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'handle' => $this->handle,
            'description' => $this->description,
            'settings' => $this->settings,
            'position' => $this->position,
            'is_enabled' => $this->is_enabled,
            'is_removable' => $this->is_removable,
            'blocks' => ThemeBlockResource::collection($this->whenLoaded('blocks')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
