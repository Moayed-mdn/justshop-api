<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'ip_address' => $this['ip_address'],
            'user_agent' => $this['user_agent'],
            'last_active_at' => $this['last_active_at'],
            'is_current' => $this['is_current'],
        ];
    }
}
