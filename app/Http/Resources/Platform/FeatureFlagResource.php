<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureFlagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'value' => $this->resource['value'],
            'has_override' => $this->resource['has_override'],
            'updated_at' => $this->resource['updated_at'],
            'metadata' => $this->resource['metadata'],
        ];
    }
}
