<?php

declare(strict_types=1);

namespace App\Http\Resources\Theme;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThemeBlockInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'container_type' => $this->container_type,
            'container_id' => $this->container_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'name' => $this->name,
            'settings' => $this->settings,
            'content' => $this->content,
            'position' => $this->position,
            'is_enabled' => $this->is_enabled,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
