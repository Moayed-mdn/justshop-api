<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum RouteDomainEnforcementModeEnum: string
{
    case OBSERVE = 'observe';
    case ENFORCE = 'enforce';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
