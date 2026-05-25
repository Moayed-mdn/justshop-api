<?php

declare(strict_types=1);

namespace App\Traits\Cms;

use App\Services\Cms\LocalizedContentResolver;
use Illuminate\Support\Facades\App;

trait LocalizedContentTrait
{
    /**
     * Resolve a localized field to a scalar value.
     */
    public function translated(string $field, ?string $locale = null): mixed
    {
        $value = $this->getAttribute($field);
        $locale = $locale ?: App::getLocale();
        $fallback = config('content.default_locale', 'en');

        return app(LocalizedContentResolver::class)->resolveLocalizedField($value, $locale, $fallback);
    }

    /**
     * Helper for backward compatibility or simple usage.
     */
    public function getLocalized(string $field, ?string $locale = null): mixed
    {
        return $this->translated($field, $locale);
    }

    /**
     * Get all supported locales for this content.
     */
    public function getSupportedLocales(): array
    {
        return config('content.editable_locales', ['en', 'ar']);
    }
}
