<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'summary' => $this->resource['summary'] ?? [],
            'charts' => $this->resource['charts'] ?? [],
            'top_stores' => $this->resource['top_stores'] ?? [],
            'recent_activity' => $this->resource['recent_activity'] ?? [],
        ];
    }
}
