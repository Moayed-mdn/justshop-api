<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];
        
        return [
            'id' => $this->id,
            'event' => $this->event,
            'actor_id' => $this->actor_id,
            'actor_type' => $this->actor_type,
            'user_id' => $this->actor_id,
            'user_name' => $metadata['actor_name'] ?? 'Unknown',
            'user_avatar' => null,
            'action' => $metadata['action'] ?? $this->extractAction($this->event),
            'resource_type' => $metadata['resource_type'] ?? $this->extractResourceType($this->event),
            'resource_id' => $metadata['resource_id'] ?? null,
            'resource_name' => $metadata['resource_name'] ?? null,
            'description' => $metadata['reason'] ?? $this->event,
            'membership_id' => $this->membership_id,
            'store_id' => $this->store_id,
            'correlation_id' => $this->correlation_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'metadata' => $metadata,
            'changes' => $metadata['changes'] ?? [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Extract action from event name (e.g., 'user.created' => 'created')
     */
    private function extractAction(string $event): string
    {
        $parts = explode('.', $event);
        return end($parts);
    }

    /**
     * Extract resource type from event name (e.g., 'user.created' => 'user')
     */
    private function extractResourceType(string $event): string
    {
        $parts = explode('.', $event);
        return $parts[0] ?? 'system';
    }
}
