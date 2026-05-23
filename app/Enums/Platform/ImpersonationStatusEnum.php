<?php

declare(strict_types=1);

namespace App\Enums\Platform;

/**
 * Impersonation Status Enum
 * 
 * Wave 6: Explicit impersonation lifecycle states.
 */
enum ImpersonationStatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case TERMINATED = 'terminated';
    case EXPIRED = 'expired';
    case DENIED = 'denied';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
