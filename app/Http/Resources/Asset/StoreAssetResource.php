<?php

declare(strict_types=1);

namespace App\Http\Resources\Asset;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreAssetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'human_file_size' => $this->human_file_size,
            'width' => $this->width,
            'height' => $this->height,
            'alt_text' => $this->alt_text,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
