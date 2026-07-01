<?php

declare(strict_types=1);

namespace App\Http\Resources\Theme;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemTemplateSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_type' => $this->type?->value ?? $this->type,
            'position' => $this->pivot->position ?? 0,
            'overrides' => $this->pivot->overrides ? (is_string($this->pivot->overrides) ? json_decode($this->pivot->overrides, true) : $this->pivot->overrides) : [],
            'settings' => $this->settings,
            'is_visible' => (bool) ($this->pivot?->is_enabled ?? $this->is_enabled),
            'blocks' => ThemeBlockResource::collection($this->whenLoaded('blocks')),
        ];
    }
}
