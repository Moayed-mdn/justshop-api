<?php

namespace App\Http\Resources;

use App\Enums\RoleEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'avatar'            => $this->getAvatarUrl(),
            'email_verified_at' => $this->email_verified_at,
            'onboarding_step'   => $this->onboarding_step,
            'has_password'      => !is_null($this->password),
            'has_google_linked' => !is_null($this->google_id),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}