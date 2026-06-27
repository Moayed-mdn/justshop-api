<?php

declare(strict_types=1);

namespace App\Http\Resources\Theme;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionSchemaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'settings' => $this->settings,
            'blocks' => $this->blocks,
            'presets' => $this->presets,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
