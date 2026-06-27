<?php

declare(strict_types=1);

namespace App\Http\Resources\Theme;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'handle' => $this->handle,
            'type' => $this->type,
            'description' => $this->description,
            'sections' => $this->sections,
            'section_order' => $this->section_order,
            'section_settings' => $this->section_settings,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'pages_count' => (int) $this->pages_count,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
