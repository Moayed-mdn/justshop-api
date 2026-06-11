<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;

class BootstrapPayloadSerializer
{
    /**
     * @return array<string, mixed>
     */
    private static function serializeStore(?object $store): ?array
    {
        if ($store === null) {
            return null;
        }

        return [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'domain' => $store->domain,
            'currency' => $store->currency,
            'role' => $store->role,
            'status' => $store->status,
            'is_active' => $store->isActive,
            'status_changed_at' => $store->statusChangedAt,
            'created_at' => $store->createdAt,
            'permissions' => $store->permissions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(GetBootstrapResponseDTO $dto): array
    {
        $result = [
            'user' => [
                'id' => $dto->user->id,
                'name' => $dto->user->name,
                'email' => $dto->user->email,
                'avatar_url' => $dto->user->avatarUrl,
                'is_email_verified' => $dto->user->isEmailVerified,
                'email_verified_at' => $dto->user->emailVerifiedAt,
            ],
            'email_verified' => $dto->user->isEmailVerified,
            'stores' => array_map(fn ($store) => self::serializeStore($store), $dto->stores),
            'active_store' => self::serializeStore($dto->activeStore),
            'active_store_id' => $dto->activeStore->id ?? null,
            'onboarding' => [
                'step' => $dto->onboarding->step,
                'completed_steps' => $dto->onboarding->completedSteps,
                'can_resume' => $dto->onboarding->canResume,
                'store_id' => $dto->onboarding->storeId,
                'is_completed' => $dto->onboarding->isCompleted,
            ],
            'permissions' => $dto->permissions,
            'capabilities' => $dto->capabilities,
            'session' => $dto->session,
            'features' => self::resolveFeatureFlags(),
            'config' => [
                'supported_locales' => $dto->config->supportedLocales,
                'default_currency' => $dto->config->defaultCurrency,
                'timezone' => $dto->config->timezone,
            ],
            'localization' => [
                'supported_locales' => $dto->config->supportedLocales,
                'default_currency' => $dto->config->defaultCurrency,
                'timezone' => $dto->config->timezone,
            ],
            'actor_context' => $dto->actorContext->value,
        ];

        // Add billing data if present (Phase 2: Subscription & Billing)
        if ($dto->billing !== null) {
            $result['billing'] = $dto->billing['billing'] ?? null;
            $result['active_store_entitlements'] = $dto->billing['active_store_entitlements'] ?? null;
        }

        return $result;
    }

    private static function resolveFeatureFlags(): array
    {
        $features = [];
        foreach (\App\Support\FeatureFlags\FeatureFlag::all() as $name => $_config) {
            $features[$name] = \App\Support\FeatureFlags\FeatureFlag::enabled($name);
        }

        return $features;
    }
}
