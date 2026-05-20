<?php

declare(strict_types=1);

namespace App\Enums\Lead;

enum LeadStatusEnum: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case CONTACTED = 'contacted';
    case ARCHIVED = 'archived';
    case SPAM = 'spam';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
