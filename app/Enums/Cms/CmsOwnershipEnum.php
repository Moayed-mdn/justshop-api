<?php

declare(strict_types=1);

namespace App\Enums\Cms;

/**
 * CMS Content Ownership Classification
 *
 * Defines whether CMS content is platform-owned or tenant-owned.
 * This is a foundational architectural boundary.
 */
enum CmsOwnershipEnum: string
{
    /**
     * Platform-level content managed by super admins.
     * Examples: Marketing pages, Blog posts
     */
    case PLATFORM = 'platform';

    /**
     * Tenant-level content managed by store owners.
     * Examples: Store-specific documentation (future)
     */
    case TENANT = 'tenant';

    /**
     * Shared infrastructure used by both platform and tenant content.
     * Examples: SEO services, sitemap generation
     */
    case SHARED = 'shared';

    public function isPlatform(): bool
    {
        return $this === self::PLATFORM;
    }

    public function isTenant(): bool
    {
        return $this === self::TENANT;
    }

    public function isShared(): bool
    {
        return $this === self::SHARED;
    }
}
