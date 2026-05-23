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
            'password_reset_flow' => 'shared', // Uses single password reset flow
            'email_verification_flow' => 'shared', // Uses single verification flow
            'notification_ownership' => 'shared', // Uses single notification system
            'token_assumptions' => 'shared', // Uses single token system
            'session_provider' => 'shared', // Uses single session provider
        ];
    }

    private function detectMigrationBlockers(): array
    {
        return [
            'shared_user_table' => true, // All actors in single 'users' table
            'shared_password_resets_table' => true, // Single password_resets table
            'shared_sessions_table' => true, // Single sessions table
            'shared_personal_access_tokens_table' => true, // Single tokens table
        ];
    }
}
