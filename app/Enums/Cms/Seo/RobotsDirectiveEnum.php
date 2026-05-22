<?php

declare(strict_types=1);

namespace App\Enums\Cms\Seo;

enum RobotsDirectiveEnum: string
{
    case INDEX_FOLLOW     = 'index,follow';
    case NOINDEX_FOLLOW   = 'noindex,follow';
    case INDEX_NOFOLLOW   = 'index,nofollow';
    case NOINDEX_NOFOLLOW = 'noindex,nofollow';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function default(): self
    {
        return self::INDEX_FOLLOW;
    }

    /**
     * Draft content must never be indexable.
     */
    public static function forDraft(): self
    {
        return self::NOINDEX_NOFOLLOW;
    }

    public function isIndexable(): bool
    {
        return in_array($this, [self::INDEX_FOLLOW, self::INDEX_NOFOLLOW], true);
    }

    public function isFollowable(): bool
    {
        return in_array($this, [self::INDEX_FOLLOW, self::NOINDEX_FOLLOW], true);
    }
}
