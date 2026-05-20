<?php

declare(strict_types=1);

namespace App\Enums\Cms\MarketingPage;

enum MarketingPageStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case SCHEDULED = 'scheduled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
