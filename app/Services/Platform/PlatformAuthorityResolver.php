<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\Auth\ActorContextEnum;
use App\Enums\Auth\PlatformAuthorityDomainEnum;
use App\Models\User;

/**
 * Platform Authority Resolver
 * 
 * Wave 6: Resolves platform authority domain for platform actors.
 * Platform authority is INDEPENDENT from merchant authority.
 */
class PlatformAuthorityResolver
{
    public function resolve(User $user): ?PlatformAuthorityDomainEnum
    {
        $actorContext = $user->getActorContext();

        return match ($actorContext) {
            ActorContextEnum::SUPER_ADMIN => PlatformAuthorityDomainEnum::PLATFORM_ADMIN,
            ActorContextEnum::SUPPORT_AGENT => PlatformAuthorityDomainEnum::SUPPORT_AGENT,
            ActorContextEnum::PLATFORM_SYSTEM => PlatformAuthorityDomainEnum::PLATFORM_SYSTEM,
            default => null,
        };
    }

    public function isPlatformActor(User $user): bool
    {
        return $this->resolve($user) !== null;
    }

    public function canAccessPlatformRoutes(User $user): bool
    {
        return $this->isPlatformActor($user);
    }

    public function canAccessSupportRoutes(User $user): bool
    {
        $authority = $this->resolve($user);

        return $authority === PlatformAuthorityDomainEnum::SUPPORT_AGENT
            || $authority === PlatformAuthorityDomainEnum::PLATFORM_ADMIN;
    }
}
