<?php

declare(strict_types=1);

namespace App\Enums\Lead;

enum LeadTypeEnum: string
{
    case CONTACT = 'contact';
    case DEMO = 'demo';
    case ENTERPRISE = 'enterprise';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
