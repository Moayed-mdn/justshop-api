<?php

declare(strict_types=1);

namespace App\Http\Resources\Storefront\Account;

use App\DTOs\Storefront\Account\StorefrontAccountBootstrapDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontAccountBootstrapResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StorefrontAccountBootstrapDTO $dto */
        $dto = $this->resource;

        return [
            'user' => [
                'id' => $dto->user->id,
                'name' => $dto->user->name,
                'email' => $dto->user->email,
                'avatar_url' => $dto->user->getAvatarUrl(),
                'is_email_verified' => $dto->user->hasVerifiedEmail(),
            ],
            'identity_context' => $dto->identityContext->toArray(),
            'session' => $dto->sessionBoundary->toArray(),
        ];
    }
}
