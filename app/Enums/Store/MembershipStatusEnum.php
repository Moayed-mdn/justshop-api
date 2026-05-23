<?php

declare(strict_types=1);

namespace App\Enums\Store;

enum MembershipStatusEnum: string
{
    case INVITED = 'invited';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DELEGATED = 'delegated';
    case TEMPORARY = 'temporary';
    case SUPPORT_MANAGED = 'support_managed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
