<?php

declare(strict_types=1);

namespace App\Http\Resources\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

class DeviceTokenResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform->value,
            'device_id' => $this->device_id,
            'device_name' => $this->device_name,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
