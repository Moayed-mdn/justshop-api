<?php

declare(strict_types=1);

namespace App\Enums\Auth;

/**
 * Platform Authority Domain Enum
 * 
 * Wave 6: Explicit platform authority isolation.
 * Platform/support is NOT merchant admin with extra permissions.
 * Platform/support is its own authority model with independent governance.
 */
enum PlatformAuthorityDomainEnum: string
{
    case PLATFORM_ADMIN = 'platform_admin';
    case SUPPORT_AGENT = 'support_agent';
    case PLATFORM_SYSTEM = 'platform_system';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isSupport(): bool
    {
        return $this === self::SUPPORT_AGENT;
    }

    public function isPlatformAdmin(): bool
    {
        return $this === self::PLATFORM_ADMIN;
    }

    public function isSystemActor(): bool
    {
        return $this === self::PLATFORM_SYSTEM;
    }

    public function allowedActorTypes(): array
    {
        return match ($this) {
            self::PLATFORM_ADMIN => [ActorContextEnum::SUPER_ADMIN],
            self::SUPPORT_AGENT => [ActorContextEnum::SUPPORT_AGENT],
            self::PLATFORM_SYSTEM => [ActorContextEnum::PLATFORM_SYSTEM],
        };
    }
}
