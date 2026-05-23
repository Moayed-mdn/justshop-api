<?php

declare(strict_types=1);

namespace App\Enums\Enterprise;

/**
 * Ownership Semantic Enum
 * 
 * Wave 6: Explicit ownership semantics.
 * 
 * Explicitly distinguish:
 * - Store owner
 * - Organization owner (future)
 * - Admin
 * - Delegated operator
 * - Support actor
 * - Temporary actor
 */
enum OwnershipSemanticEnum: string
{
    case STORE_OWNER = 'store_owner';
    case ORGANIZATION_OWNER = 'organization_owner'; // Future
    case ADMIN = 'admin';
    case DELEGATED_OPERATOR = 'delegated_operator';
    case SUPPORT_ACTOR = 'support_actor';
    case TEMPORARY_ACTOR = 'temporary_actor';
    case MEMBER = 'member';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isOwner(): bool
    {
        return in_array($this, [self::STORE_OWNER, self::ORGANIZATION_OWNER], true);
    }

    public function hasAdminPrivileges(): bool
    {
        return in_array($this, [self::STORE_OWNER, self::ORGANIZATION_OWNER, self::ADMIN], true);
    }

    public function isDelegated(): bool
    {
        return $this === self::DELEGATED_OPERATOR;
    }

    public function isSupport(): bool
    {
        return $this === self::SUPPORT_ACTOR;
    }

    public function isTemporary(): bool
    {
        return $this === self::TEMPORARY_ACTOR;
    }
}
