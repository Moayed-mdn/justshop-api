<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Auth\ActorContextEnum;
use App\Enums\Auth\AuthDomainEnum;
use App\Enums\Auth\IdentityProviderEnum;
use App\Models\User;

/**
 * Provider Governance Service
 * 
 * Wave 6: Prepare eventual provider separation.
 * NOT activated yet. Preparation only.
 * 
 * Detects and documents:
 * - Shared provider assumptions
 * - Shared password reset flows
 * - Shared verification flows
 * - Shared notification ownership
 * - Shared token assumptions
 */
class ProviderGovernanceService
{
    public function resolveProvider(User $user): IdentityProviderEnum
    {
        // Wave 6: All actors still use shared provider
        // Future: Resolve based on actor context
        return IdentityProviderEnum::SHARED;
    }

    public function resolveProviderForAuthDomain(AuthDomainEnum $authDomain): IdentityProviderEnum
    {
        // Wave 6: All domains still use shared provider
        // Future: Resolve based on auth domain
        return IdentityProviderEnum::SHARED;
    }

    public function resolveProviderForActorContext(ActorContextEnum $actorContext): IdentityProviderEnum
    {
        // Wave 6: All actors still use shared provider
        // Future: Resolve based on actor context
        return match ($actorContext) {
            ActorContextEnum::MERCHANT => IdentityProviderEnum::SHARED, // Future: MERCHANT
            ActorContextEnum::CUSTOMER => IdentityProviderEnum::SHARED, // Future: CUSTOMER
            ActorContextEnum::SUPER_ADMIN,
            ActorContextEnum::SUPPORT_AGENT,
            ActorContextEnum::PLATFORM_SYSTEM => IdentityProviderEnum::SHARED, // Future: PLATFORM
        };
    }

    public function isProviderSeparationReady(): bool
    {
        // Wave 6: Provider separation NOT ready yet
        // Future: Check readiness criteria
        return false;
    }

    public function getProviderReadinessReport(): array
    {
        return [
            'provider_separation_ready' => $this->isProviderSeparationReady(),
            'current_provider' => IdentityProviderEnum::SHARED->value,
            'shared_assumptions' => $this->detectSharedAssumptions(),
            'migration_blockers' => $this->detectMigrationBlockers(),
        ];
    }

    private function detectSharedAssumptions(): array
    {
        return [
            'shared_model_assumptions' => [
                'user_model' => 'App\Models\User shared by all domains',
                'store_model' => 'App\Models\Store shared by merchant/platform',
            ],
            'shared_provider_assumptions' => [
                'eloquent_provider' => 'Single EloquentUserProvider for all guards',
                'config_auth_providers' => 'Single "users" provider in config/auth.php',
            ],
            'shared_notification_assumptions' => [
                'database_notifications' => 'Shared notifications table',
                'mail_notifications' => 'Shared mail templates without domain branding',
            ],
            'shared_password_broker_assumptions' => [
                'broker' => 'Single "users" password broker',
                'tokens_table' => 'Shared password_reset_tokens table',
            ],
            'shared_auth_event_assumptions' => [
                'events' => 'Generic Illuminate\Auth\Events shared across domains',
            ],
            'shared_email_verification_assumptions' => [
                'verification_flow' => 'Shared VerifyEmail notification and route',
            ],
        ];
    }

    private function detectMigrationBlockers(): array
    {
        return [
            'hard_blockers' => [
                'shared_user_table' => 'Polymorphic relations to "users" table in all domains',
                'shared_auth_config' => 'Sanctum/Fortify assumptions about single user model',
            ],
            'soft_blockers' => [
                'shared_session_driver' => 'Common session driver and cookie domain',
                'shared_cache_namespace' => 'Common cache prefix for auth data',
            ],
            'hidden_coupling' => [
                'pivot_membership' => 'store_user pivot table couples merchant and user domains',
            ],
            'migration_safe_seams' => [
                'actor_context' => 'ActorContextEnum allows logic branching',
                'auth_domain' => 'AuthDomainEnum allows route/middleware branching',
            ],
        ];
    }
}
