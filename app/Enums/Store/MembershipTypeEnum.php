<?php

declare(strict_types=1);

namespace App\Enums\Store;

enum MembershipTypeEnum: string
{
    case STORE_OWNER = 'store_owner';
    case ORGANIZATION_OWNER = 'organization_owner';
    case ADMIN = 'admin';
    case DELEGATED_OPERATOR = 'delegated_operator';
    case SUPPORT_ACTOR = 'support_actor';
    case TEMPORARY_ACTOR = 'temporary_actor';
    case INHERITED = 'inherited';
    case ORGANIZATION_SCOPED = 'organization_scoped';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
