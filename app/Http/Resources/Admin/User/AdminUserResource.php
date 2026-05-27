<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'role'              => $this->store_role ?? $this->roles->first()?->name ?? 'customer',
            'store_id'          => $this->when(
                $this->relationLoaded('stores'),
                fn() => $this->stores->first()?->id
            ),
            'is_active'         => $this->is_active ?? true,
            'deleted_at'        => $this->deleted_at,
            'email_verified_at' => $this->email_verified_at,
            'orders_count'      => $this->when(
                $this->relationLoaded('orders'),
                fn() => $this->orders->count()
            ),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
