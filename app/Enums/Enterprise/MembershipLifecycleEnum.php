<?php

declare(strict_types=1);

namespace App\Enums\Enterprise;

/**
 * Membership Lifecycle Enum
 * 
 * Wave 6: Explicit membership lifecycle vocabulary.
 * 
 * The old store_user.role model is no longer sufficient for enterprise scale.
 * Prepare scalable enterprise authority semantics.
 */
enum MembershipLifecycleEnum: string
{
    case INVITED = 'invited';
    case PENDING_ACTIVATION = 'pending_activation';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REVOKED = 'revoked';
    case DELEGATED = 'delegated';
    case TEMPORARY = 'temporary';
    case SUPPORT_MANAGED = 'support_managed';
    case INHERITED = 'inherited';
    case ORGANIZATION_SCOPED = 'organization_scoped';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function requiresApproval(): bool
    {
        return in_array($this, [self::INVITED, self::DELEGATED, self::TEMPORARY], true);
    }

    public function isManagedBySupport(): bool
    {
        return $this === self::SUPPORT_MANAGED;
    }
}
