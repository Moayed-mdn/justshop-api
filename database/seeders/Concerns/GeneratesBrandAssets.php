<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Generates on-brand, dependency-free SVG assets (logo, favicon, hero
 * backdrops) directly from a color palette.
 *
 * Why generated SVG instead of hotlinked stock photography:
 *  - Zero risk of broken images: no external host, no dead links, no rate
 *    limiting, nothing that can 404 (the previous seeders referenced a
 *    fabricated Unsplash id and random lifestyle photos used as a "logo").
 *  - Perfect color harmony: the logo, favicon, and hero art are all derived
 *    from the exact same tokens as the buttons and theme chrome, so nothing
 *    can visually clash.
 *  - Portable: pure vector markup, no image library (e.g. GD) required.
 */
trait GeneratesBrandAssets
{
    /**
     * The store's canonical brand palette.
     *
     * Store branding (logo/favicon/banners) is seeded by StoreAssetsSeeder
     * BEFORE any Theme exists (see DatabaseSeeder's ordering comment "Must
     * run before themes"), so it can't read a theme's colors — it owns this
     * palette instead. RichThemeSeeder's first ("Aurora") theme variation
     * intentionally reuses these exact values so the published storefront
     * always matches the logo.
     */
    protected function brandPalette(): array
    {
        return [
            'primary' => '#4F46E5',
            'secondary' => '#0EA5E9',
            'accent' => '#F97316',
            'background' => '#FFFFFF',
            'surface' => '#F8FAFC',
            'text' => '#0F172A',
            'textMuted' => '#64748B',
            'border' => '#E2E8F0',
            'success' => '#16A34A',
            'warning' => '#F59E0B',
            'error' => '#DC2626',
        ];
    }

    /**
     * Up to two initials for a monogram mark, e.g. "JustShop Demo" -> "JD".
     */
    protected function brandInitials(string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));

        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }

        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 2));
        }

        return 'S';
    }

    /**
     * Horizontal "icon + wordmark" logo lockup: a rounded gradient
     * monogram badge followed by the store name.
     */
    protected function logoSvg(string $storeName, string $primary, string $secondary, string $textColor = '#111827'): string
    {
        $initials = e($this->brandInitials($storeName));
        $name = e($storeName);
        $gradId = 'lg' . substr(md5($primary . $secondary), 0, 8);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 84" width="320" height="84">
          <defs>
            <linearGradient id="{$gradId}" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$primary}"/>
              <stop offset="1" stop-color="{$secondary}"/>
            </linearGradient>
          </defs>
          <rect x="0" y="10" width="64" height="64" rx="18" fill="url(#{$gradId})"/>
          <text x="32" y="50" font-family="Helvetica Neue, Arial, sans-serif" font-size="26" font-weight="700" fill="#FFFFFF" text-anchor="middle">{$initials}</text>
          <text x="80" y="49" font-family="Helvetica Neue, Arial, sans-serif" font-size="30" font-weight="700" fill="{$textColor}">{$name}</text>
        </svg>
        SVG;
    }

    /**
     * Square icon-only mark for favicons / app icons.
     */
    protected function iconSvg(string $storeName, string $primary, string $secondary): string
    {
        $initials = e($this->brandInitials($storeName));
        $gradId = 'ic' . substr(md5($primary . $secondary), 0, 8);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
          <defs>
            <linearGradient id="{$gradId}" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$primary}"/>
              <stop offset="1" stop-color="{$secondary}"/>
            </linearGradient>
          </defs>
          <rect width="64" height="64" rx="16" fill="url(#{$gradId})"/>
          <text x="32" y="42" font-family="Helvetica Neue, Arial, sans-serif" font-size="26" font-weight="700" fill="#FFFFFF" text-anchor="middle">{$initials}</text>
        </svg>
        SVG;
    }

    /**
     * Abstract "mesh gradient" hero backdrop generated purely from the
     * theme's own tokens, so it can never clash with the rest of the page
     * and never depends on an external host.
     *
     * Built from radial gradients that fade to transparent (not flat
     * circles behind a blur filter): gradients render identically and
     * reliably everywhere, whereas <feGaussianBlur> support is patchy, and
     * a fade-to-transparent edge blends two overlapping colors far more
     * softly than two flat semi-transparent discs (which produce a muddy
     * "mixed paint" patch where they cross).
     *
     * @param string $style One of 'modern' | 'dark' | 'minimal'.
     */
    protected function heroBannerSvg(string $primary, string $secondary, string $accent, string $background, string $style = 'modern'): string
    {
        if ($style === 'minimal') {
            return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900" width="1600" height="900">
              <rect width="1600" height="900" fill="{$background}"/>
              <circle cx="1180" cy="450" r="360" fill="none" stroke="{$primary}" stroke-opacity="0.18" stroke-width="2"/>
              <circle cx="1180" cy="450" r="260" fill="none" stroke="{$primary}" stroke-opacity="0.28" stroke-width="2"/>
              <line x1="0" y1="900" x2="1600" y2="620" stroke="{$primary}" stroke-opacity="0.10" stroke-width="1"/>
              <rect x="120" y="700" width="140" height="6" fill="{$accent}"/>
            </svg>
            SVG;
        }

        $idPrimary = 'gp' . substr(md5($primary . $style), 0, 6);
        $idSecondary = 'gs' . substr(md5($secondary . $style), 0, 6);
        $idAccent = 'ga' . substr(md5($accent . $style), 0, 6);
        $peak = $style === 'dark' ? '0.85' : '0.50';
        $mid = $style === 'dark' ? '0.35' : '0.18';

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900" width="1600" height="900">
          <defs>
            <radialGradient id="{$idSecondary}" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="{$secondary}" stop-opacity="{$peak}"/>
              <stop offset="55%" stop-color="{$secondary}" stop-opacity="{$mid}"/>
              <stop offset="100%" stop-color="{$secondary}" stop-opacity="0"/>
            </radialGradient>
            <radialGradient id="{$idPrimary}" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="{$primary}" stop-opacity="{$peak}"/>
              <stop offset="55%" stop-color="{$primary}" stop-opacity="{$mid}"/>
              <stop offset="100%" stop-color="{$primary}" stop-opacity="0"/>
            </radialGradient>
            <radialGradient id="{$idAccent}" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="{$accent}" stop-opacity="{$peak}"/>
              <stop offset="60%" stop-color="{$accent}" stop-opacity="{$mid}"/>
              <stop offset="100%" stop-color="{$accent}" stop-opacity="0"/>
            </radialGradient>
          </defs>
          <rect width="1600" height="900" fill="{$background}"/>
          <circle cx="1500" cy="60" r="560" fill="url(#{$idSecondary})"/>
          <circle cx="60" cy="900" r="560" fill="url(#{$idPrimary})"/>
          <circle cx="1420" cy="840" r="340" fill="url(#{$idAccent})"/>
        </svg>
        SVG;
    }

    /**
     * Persist a generated SVG to the public disk and return the relative
     * storage path.
     *
     * Deliberately NOT a data: URI: app/Support/Media/MediaUrl::resolve()
     * only recognizes absolute http(s) URLs or storage-relative paths — a
     * data: URI falls through to Storage::disk('public')->url($value) and
     * gets mangled into a broken link. A relative path resolves correctly.
     */
    protected function writeBrandAsset(string $relativePath, string $svg): string
    {
        Storage::disk('public')->put($relativePath, $svg);

        return $relativePath;
    }

    /**
     * Darken a hex color by a percentage (moved here from RichThemeSeeder
     * so any seeder that needs a derived shade can share it).
     */
    protected function darkenColor(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));

        return sprintf('#%02X%02X%02X', (int) $r, (int) $g, (int) $b);
    }

    /**
     * Pick black or white text for legible contrast against a given
     * background color, using relative luminance rather than assuming.
     */
    protected function readableTextColor(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return '#111827';
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        return $luminance > 0.6 ? '#111827' : '#FFFFFF';
    }
}
