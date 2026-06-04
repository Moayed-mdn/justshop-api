<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Lead;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminLeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'message' => $this->message,
            'source_page' => $this->source_page,
            'metadata' => $this->metadata ?? [],
            'resolution_notes' => $this->resolution_notes,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'resolved_by' => $this->when(
                $this->resolvedByUser !== null,
                fn (): array => [
                    'id' => $this->resolvedByUser->id,
                    'name' => $this->resolvedByUser->name,
                    'email' => $this->resolvedByUser->email,
                ]
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
