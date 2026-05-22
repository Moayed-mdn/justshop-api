<?php

declare(strict_types=1);

namespace App\Services\Cms\Seo;

/**
 * Generates canonical URLs and hreflang alternate maps.
 *
 * Strategy:
 * - Canonical always points to the PRIMARY locale (en) URL.
 * - Each locale gets its own alternate URL.
 * - URLs are built from FRONTEND_URL env variable.
 * - Route prefix is the segment before the slug (e.g. "blog", "docs").
 *
 * Example:
 *   FRONTEND_URL = https://example.com
 *   slugMap      = ["en" => "about", "ar" => "من-نحن"]
 *   routePrefix  = ""
 *
 *   canonical    = https://example.com/about
 *   alternates   = ["en" => "https://example.com/about",
 *                   "ar" => "https://example.com/ar/من-نحن"]
 *
 * Locale URL strategy:
 *   - English (primary): no locale prefix → /about
 *   - Arabic (secondary): locale prefix   → /ar/من-نحن
 *
 * This avoids duplicate content for the primary locale while
 * giving search engines correct hreflang signals for secondary locales.
 */
final class CanonicalUrlService
{
    private string $frontendUrl;
    private string $primaryLocale;

    /** @var string[] */
    private array $supportedLocales;

    public function __construct()
    {
        $this->frontendUrl      = rtrim((string) config('app.frontend_url', ''), '/');
        $this->primaryLocale    = (string) config('app.locale', 'en');
        $this->supportedLocales = (array) config('app.supported_locales', ['en', 'ar']);
    }

    /**
     * Generate the canonical URL from the slug map.
     * Always uses primary locale slug without locale prefix.
     *
     * @param array<string, mixed> $slugMap
     */
    public function generateCanonical(
        array $slugMap,
        string $fallback = 'en',
        string $routePrefix = '',
    ): ?string {
        $slug = $slugMap[$this->primaryLocale]
            ?? $slugMap[$fallback]
            ?? null;

        if ($slug === null || $slug === '') {
            return null;
        }

        return $this->buildUrl($this->primaryLocale, (string) $slug, $routePrefix);
    }

    /**
     * Generate hreflang alternate URLs for all locales in the slug map.
     *
     * @param  array<string, mixed> $slugMap
     * @return array<string, string>
     */
    public function generateAlternates(
        array $slugMap,
        string $routePrefix = '',
    ): array {
        $alternates = [];

        foreach ($this->supportedLocales as $locale) {
            $slug = $slugMap[$locale] ?? $slugMap[$this->primaryLocale] ?? null;

            if ($slug === null || $slug === '') {
                continue;
            }

            $alternates[$locale] = $this->buildUrl($locale, (string) $slug, $routePrefix);
        }

        // x-default points to primary locale
        if (isset($alternates[$this->primaryLocale])) {
            $alternates['x-default'] = $alternates[$this->primaryLocale];
        }

        return $alternates;
    }

    /**
     * Build a full URL for a given locale + slug.
     *
     * Primary locale: no prefix  → https://example.com/[prefix/]slug
     * Other locales:  with prefix → https://example.com/ar/[prefix/]slug
     */
    private function buildUrl(string $locale, string $slug, string $routePrefix): string
    {
        $segments = [];

        // Non-primary locales get locale path prefix
        if ($locale !== $this->primaryLocale) {
            $segments[] = $locale;
        }

        if ($routePrefix !== '') {
            $segments[] = trim($routePrefix, '/');
        }

        $segments[] = trim($slug, '/');

        $path = implode('/', array_filter($segments));

        return $this->frontendUrl . '/' . $path;
    }
}
