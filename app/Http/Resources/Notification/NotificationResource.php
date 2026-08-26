<?php

declare(strict_types=1);

namespace App\Http\Resources\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * $this refers to an Illuminate\Notifications\DatabaseNotification row.
 * Its `data` column holds the payload built by each Notification class's
 * toDatabase() — see docs/notifications/CLIENT_PAYLOADS.md for the shape
 * every notification type follows.
 */
class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $payload = $this->data;

        return [
            'id' => $this->id,
            'type' => $payload['type'] ?? null,
            'title' => $payload['title'] ?? null,
            'body' => $payload['body'] ?? null,
            'entity_type' => $payload['entity_type'] ?? null,
            'entity_id' => $payload['entity_id'] ?? null,
            'route' => $payload['route'] ?? null,
            'data' => $payload['data'] ?? [],
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
