<?php

declare(strict_types=1);

namespace App\Enums\Auth;

/**
 * Identity Provider Enum
 * 
 * Wave 6: Prepare eventual separation between merchant and customer identity providers.
 * NOT activated yet. Preparation only.
 * 
 * Current state: SHARED provider (users table)
 * Future state: MERCHANT provider + CUSTOMER provider
 */
enum IdentityProviderEnum: string
{
    case SHARED = 'shared'; // Current: all actors use 'users' table
    case MERCHANT = 'merchant'; // Future: merchant actors use merchant provider
    case CUSTOMER = 'customer'; // Future: customer actors use customer provider
    case PLATFORM = 'platform'; // Future: platform actors use platform provider

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isShared(): bool
    {
        return $this === self::SHARED;
    }

    public function isSeparated(): bool
    {
        return !$this->isShared();
    }
}
