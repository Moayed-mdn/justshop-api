<?php

declare(strict_types=1);

namespace App\Enums\Cms\Marketing;

/**
 * Marketing Page Publishing Status
 * 
 * Shared enum for both Platform and Store marketing pages
 */
enum MarketingPageStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case SCHEDULED = 'scheduled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }

    public function isScheduled(): bool
    {
        return $this === self::SCHEDULED;
    }
}
