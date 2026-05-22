<?php

declare(strict_types=1);

namespace App\Http\Resources\Storefront\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontAccountUserResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->getAvatarUrl(),
            'is_email_verified' => $this->hasVerifiedEmail(),
        ];
    }
}
