<?php

declare(strict_types=1);

namespace App\Contracts\Cms;

/**
 * Contract for CMS entities with JSON-localized content.
 *
 * All CMS content types store translatable fields as JSON maps:
 * {"en": "English", "ar": "العربية"}
 */
interface HasLocalizedContent
{
    /**
     * Resolve a localized field to a scalar value.
     *
     * @param string $field Field name (e.g., 'title', 'slug', 'content')
     * @param string|null $locale Target locale (defaults to app locale)
     * @return mixed Resolved value
     */
    public function translated(string $field, ?string $locale = null): mixed;

    /**
     * Get all supported locales for this content.
     *
     * @return array<string>
     */
    public function getSupportedLocales(): array;
}
