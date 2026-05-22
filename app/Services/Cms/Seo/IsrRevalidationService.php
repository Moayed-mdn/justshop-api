<?php

declare(strict_types=1);

namespace App\Services\Cms\Seo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Triggers Next.js ISR on-demand revalidation after publish actions.
 *
 * How it works:
 * 1. Admin publishes a CMS page.
 * 2. PublishMarketingPageAction calls this service.
 * 3. This service sends a POST request to Next.js revalidation endpoint.
 * 4. Next.js re-generates the page at the next request (ISR).
 *
 * Next.js endpoint example:
 *   POST /api/revalidate
 *   { "secret": "...", "paths": ["/about", "/ar/من-نحن"] }
 *
 * Configuration:
 *   FRONTEND_REVALIDATION_URL=https://example.com/api/revalidate
 *   FRONTEND_REVALIDATION_SECRET=your-secret
 *
 * If no URL is configured, revalidation is silently skipped.
 * Failures are logged but never throw — they must not break publish.
 */
final class IsrRevalidationService
{
    public function revalidatePaths(array $paths): void
    {
        $url    = config('services.nextjs.revalidation_url');
        $secret = config('services.nextjs.revalidation_secret');

        if (empty($url)) {
            return;
        }

        try {
            Http::timeout(5)
                ->post((string) $url, [
                    'secret' => $secret,
                    'paths'  => $paths,
                ]);
        } catch (\Throwable $e) {
            // Never fail publish because of revalidation errors
            Log::warning('ISR revalidation failed', [
                'paths' => $paths,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build frontend paths from a slug map.
     * Primary locale: /slug
     * Other locales: /locale/slug
     *
     * @param  array<string, string> $slugMap
     * @param  string                $routePrefix
     * @return string[]
     */
    public function pathsFromSlugMap(array $slugMap, string $routePrefix = ''): array
    {
        $primaryLocale = (string) config('app.locale', 'en');
        $paths = [];

        foreach ($slugMap as $locale => $slug) {
            $segments = [];

            if ($locale !== $primaryLocale) {
                $segments[] = $locale;
            }

            if ($routePrefix !== '') {
                $segments[] = trim($routePrefix, '/');
            }

            $segments[] = trim((string) $slug, '/');

            $paths[] = '/' . implode('/', array_filter($segments));
        }

        return $paths;
    }
}
