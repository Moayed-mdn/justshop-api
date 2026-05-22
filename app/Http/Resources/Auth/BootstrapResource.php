<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BootstrapResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO $dto */
        $dto = $this->resource;

        return [
            'user' => [
                'id' => $dto->user->id,
                'name' => $dto->user->name,
                'email' => $dto->user->email,
                'avatar_url' => $dto->user->avatarUrl,
                'is_email_verified' => $dto->user->isEmailVerified,
            ],
            'stores' => array_map(fn($store) => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'domain' => $store->domain,
                'currency' => $store->currency,
                'role' => $store->role,
            ], $dto->stores),
            'active_store' => $dto->activeStore ? [
                'id' => $dto->activeStore->id,
                'name' => $dto->activeStore->name,
                'slug' => $dto->activeStore->slug,
                'domain' => $dto->activeStore->domain,
                'currency' => $dto->activeStore->currency,
                'role' => $dto->activeStore->role,
            ] : null,
            'onboarding' => [
                'step' => $dto->onboarding->step,
                'is_completed' => $dto->onboarding->isCompleted,
            ],
            'permissions' => $dto->permissions,
            'capabilities' => $dto->capabilities,
            'config' => [
                'supported_locales' => $dto->config->supportedLocales,
                'default_currency' => $dto->config->defaultCurrency,
                'timezone' => $dto->config->timezone,
            ],
            'actor_context' => $dto->actorContext->value,
        ];
    }
}
