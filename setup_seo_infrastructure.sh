#!/usr/bin/env bash

# =============================================================================
# SEO Infrastructure Foundation — Marketing CMS
# Laravel Architecture: strict DTO → Action → Repository → Resource
# =============================================================================

set -e

PROJECT_ROOT="/home/leader/projects/laravel/laratenant-backend"
cd "$PROJECT_ROOT"

echo ""
echo "============================================================"
echo " SEO Infrastructure Foundation — Marketing CMS"
echo "============================================================"
echo ""

# =============================================================================
# HELPER
# =============================================================================

write_file() {
    local path="$1"
    local content="$2"
    mkdir -p "$(dirname "$path")"
    printf '%s' "$content" > "$path"
    echo "  ✅ $path"
}

echo "▶ Creating Enums..."

# =============================================================================
# ENUMS
# =============================================================================

write_file "app/Enums/Cms/Seo/RobotsDirectiveEnum.php" '<?php

declare(strict_types=1);

namespace App\Enums\Cms\Seo;

enum RobotsDirectiveEnum: string
{
    case INDEX_FOLLOW     = '"'"'index,follow'"'"';
    case NOINDEX_FOLLOW   = '"'"'noindex,follow'"'"';
    case INDEX_NOFOLLOW   = '"'"'index,nofollow'"'"';
    case NOINDEX_NOFOLLOW = '"'"'noindex,nofollow'"'"';

    public static function values(): array
    {
        return array_column(self::cases(), '"'"'value'"'"');
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
'

write_file "app/Enums/Cms/Seo/SitemapChangefreqEnum.php" '<?php

declare(strict_types=1);

namespace App\Enums\Cms\Seo;

enum SitemapChangefreqEnum: string
{
    case ALWAYS  = '"'"'always'"'"';
    case HOURLY  = '"'"'hourly'"'"';
    case DAILY   = '"'"'daily'"'"';
    case WEEKLY  = '"'"'weekly'"'"';
    case MONTHLY = '"'"'monthly'"'"';
    case YEARLY  = '"'"'yearly'"'"';
    case NEVER   = '"'"'never'"'"';

    public static function values(): array
    {
        return array_column(self::cases(), '"'"'value'"'"');
    }
}
'

echo "▶ Creating DTOs..."

# =============================================================================
# DTOs
# =============================================================================

write_file "app/DTOs/Cms/Seo/SeoMetaDTO.php" '<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Seo;

use App\Enums\Cms\Seo\RobotsDirectiveEnum;

/**
 * Typed SEO metadata payload.
 *
 * This DTO represents the normalized SEO contract for ALL marketing CMS
 * entities (MarketingPage, BlogPost, CmsDocument).
 *
 * Localized fields (meta_title, meta_description, og_image) are stored
 * as locale maps: ["en" => "...", "ar" => "..."]
 *
 * Non-localized fields (canonical_url, robots, structured_data) are
 * stored as scalar values — canonical is always the en/primary URL.
 */
final class SeoMetaDTO
{
    public function __construct(
        /** @var array<string, string|null> e.g. ["en" => "Page Title", "ar" => "عنوان"] */
        public readonly array $metaTitle,

        /** @var array<string, string|null> */
        public readonly array $metaDescription,

        /**
         * Canonical URL — always the primary locale URL.
         * Generated from FRONTEND_URL + primary slug.
         * NOT localized — one canonical per page.
         */
        public readonly ?string $canonicalUrl,

        /**
         * OG image — may be locale-aware for different language markets.
         * @var array<string, string|null>|string|null
         */
        public readonly array|string|null $ogImage,

        public readonly RobotsDirectiveEnum $robots,

        /**
         * Structured data payload for JSON-LD.
         * Frontend renders this as <script type="application/ld+json">.
         * @var array<string, mixed>|null
         */
        public readonly ?array $structuredData,

        /**
         * OG title — defaults to meta_title if not provided.
         * @var array<string, string|null>
         */
        public readonly array $ogTitle,

        /**
         * OG description — defaults to meta_description if not provided.
         * @var array<string, string|null>
         */
        public readonly array $ogDescription,

        /**
         * Twitter card type.
         */
        public readonly string $twitterCard,
    ) {}

    /**
     * Build from raw seo JSON array stored in marketing_pages.seo column.
     */
    public static function fromArray(array $seo): self
    {
        return new self(
            metaTitle: self::normalizeLocaleMap($seo['"'"'meta_title'"'"'] ?? []),
            metaDescription: self::normalizeLocaleMap($seo['"'"'meta_description'"'"'] ?? []),
            canonicalUrl: isset($seo['"'"'canonical_url'"'"']) && is_string($seo['"'"'canonical_url'"'"'])
                ? $seo['"'"'canonical_url'"'"']
                : null,
            ogImage: $seo['"'"'og_image'"'"'] ?? null,
            robots: isset($seo['"'"'robots'"'"']) && is_string($seo['"'"'robots'"'"'])
                ? (RobotsDirectiveEnum::tryFrom($seo['"'"'robots'"'"']) ?? RobotsDirectiveEnum::default())
                : RobotsDirectiveEnum::default(),
            structuredData: isset($seo['"'"'structured_data'"'"']) && is_array($seo['"'"'structured_data'"'"'])
                ? $seo['"'"'structured_data'"'"']
                : null,
            ogTitle: self::normalizeLocaleMap($seo['"'"'og_title'"'"'] ?? []),
            ogDescription: self::normalizeLocaleMap($seo['"'"'og_description'"'"'] ?? []),
            twitterCard: isset($seo['"'"'twitter_card'"'"']) && is_string($seo['"'"'twitter_card'"'"'])
                ? $seo['"'"'twitter_card'"'"']
                : '"'"'summary_large_image'"'"',
        );
    }

    /**
     * Build from per-locale flat fields (CmsDocument / BlogPostTranslation pattern).
     *
     * @param array<string, mixed> $localeMap  e.g. ["en" => [...fields], "ar" => [...fields]]
     */
    public static function fromTranslationRows(array $localeMap): self
    {
        $metaTitle       = [];
        $metaDescription = [];
        $ogImage         = [];
        $ogTitle         = [];
        $ogDescription   = [];
        $robots          = RobotsDirectiveEnum::default();
        $canonicalUrl    = null;
        $twitterCard     = '"'"'summary_large_image'"'"';

        foreach ($localeMap as $locale => $fields) {
            if (!is_array($fields)) {
                continue;
            }
            $metaTitle[$locale]       = $fields['"'"'meta_title'"'"'] ?? null;
            $metaDescription[$locale] = $fields['"'"'meta_description'"'"'] ?? null;
            $ogImage[$locale]         = $fields['"'"'og_image'"'"'] ?? null;
            $ogTitle[$locale]         = $fields['"'"'og_title'"'"'] ?? $fields['"'"'meta_title'"'"'] ?? null;
            $ogDescription[$locale]   = $fields['"'"'og_description'"'"'] ?? $fields['"'"'meta_description'"'"'] ?? null;

            // canonical is shared; take from any locale (prefer en)
            if ($locale === '"'"'en'"'"' && isset($fields['"'"'canonical_url'"'"'])) {
                $canonicalUrl = $fields['"'"'canonical_url'"'"'];
            }
            if ($canonicalUrl === null && isset($fields['"'"'canonical_url'"'"'])) {
                $canonicalUrl = $fields['"'"'canonical_url'"'"'];
            }

            // robots: use en, else first defined
            if ($locale === '"'"'en'"'"' && isset($fields['"'"'robots'"'"'])) {
                $robots = RobotsDirectiveEnum::tryFrom($fields['"'"'robots'"'"']) ?? RobotsDirectiveEnum::default();
            }
        }

        return new self(
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
            canonicalUrl: $canonicalUrl,
            ogImage: $ogImage,
            robots: $robots,
            structuredData: null,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            twitterCard: $twitterCard,
        );
    }

    /**
     * Override robots to noindex for draft/unpublished content.
     */
    public function withDraftRobots(): self
    {
        return new self(
            metaTitle: $this->metaTitle,
            metaDescription: $this->metaDescription,
            canonicalUrl: $this->canonicalUrl,
            ogImage: $this->ogImage,
            robots: RobotsDirectiveEnum::forDraft(),
            structuredData: $this->structuredData,
            ogTitle: $this->ogTitle,
            ogDescription: $this->ogDescription,
            twitterCard: $this->twitterCard,
        );
    }

    /**
     * Serialize to array for storage in JSON column.
     */
    public function toArray(): array
    {
        return [
            '"'"'meta_title'"'"'       => $this->metaTitle,
            '"'"'meta_description'"'"' => $this->metaDescription,
            '"'"'canonical_url'"'"'    => $this->canonicalUrl,
            '"'"'og_image'"'"'         => $this->ogImage,
            '"'"'og_title'"'"'         => $this->ogTitle,
            '"'"'og_description'"'"'   => $this->ogDescription,
            '"'"'robots'"'"'           => $this->robots->value,
            '"'"'twitter_card'"'"'     => $this->twitterCard,
            '"'"'structured_data'"'"'  => $this->structuredData,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string, string|null>
     */
    private static function normalizeLocaleMap(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            return ['"'"'en'"'"' => $value];
        }
        return [];
    }
}
'

write_file "app/DTOs/Cms/Seo/ResolvedSeoDTO.php" '<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Seo;

use App\Enums\Cms\Seo\RobotsDirectiveEnum;

/**
 * Locale-resolved SEO payload for API responses.
 *
 * All fields are scalar — no locale maps.
 * This is what the frontend (Next.js) receives and uses directly
 * in generateMetadata() without any client-side resolution.
 */
final class ResolvedSeoDTO
{
    public function __construct(
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly ?string $canonicalUrl,
        public readonly ?string $ogImage,
        public readonly ?string $ogTitle,
        public readonly ?string $ogDescription,
        public readonly string  $robots,
        public readonly bool    $isIndexable,
        public readonly bool    $isFollowable,
        public readonly string  $twitterCard,

        /**
         * hreflang alternates for <link rel="alternate" hreflang="...">
         * @var array<string, string> e.g. ["en" => "https://...", "ar" => "https://..."]
         */
        public readonly array $alternates,

        /**
         * Structured data — frontend renders as JSON-LD.
         * @var array<string, mixed>|null
         */
        public readonly ?array $structuredData,
    ) {}

    public function toArray(): array
    {
        return [
            '"'"'meta_title'"'"'       => $this->metaTitle,
            '"'"'meta_description'"'"' => $this->metaDescription,
            '"'"'canonical_url'"'"'    => $this->canonicalUrl,
            '"'"'og_image'"'"'         => $this->ogImage,
            '"'"'og_title'"'"'         => $this->ogTitle,
            '"'"'og_description'"'"'   => $this->ogDescription,
            '"'"'robots'"'"'           => $this->robots,
            '"'"'is_indexable'"'"'     => $this->isIndexable,
            '"'"'is_followable'"'"'    => $this->isFollowable,
            '"'"'twitter_card'"'"'     => $this->twitterCard,
            '"'"'alternates'"'"'       => $this->alternates,
            '"'"'structured_data'"'"'  => $this->structuredData,
        ];
    }
}
'

write_file "app/DTOs/Cms/Sitemap/SitemapEntryDTO.php" '<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Sitemap;

use App\Enums\Cms\Seo\SitemapChangefreqEnum;
use Carbon\Carbon;

/**
 * A single sitemap entry.
 * Locale-aware: alternates map holds all locale URLs for hreflang.
 */
final class SitemapEntryDTO
{
    public function __construct(
        /** Canonical URL (primary locale) */
        public readonly string $loc,

        public readonly ?Carbon $lastmod,

        public readonly SitemapChangefreqEnum $changefreq,

        /** 0.0 to 1.0 */
        public readonly float $priority,

        /**
         * hreflang alternates.
         * @var array<string, string> ["en" => "https://...", "ar" => "https://..."]
         */
        public readonly array $alternates,
    ) {}

    public function toArray(): array
    {
        return [
            '"'"'loc'"'"'        => $this->loc,
            '"'"'lastmod'"'"'    => $this->lastmod?->toAtomString(),
            '"'"'changefreq'"'"' => $this->changefreq->value,
            '"'"'priority'"'"'   => $this->priority,
            '"'"'alternates'"'"' => $this->alternates,
        ];
    }
}
'

echo "▶ Creating Services..."

# =============================================================================
# SERVICES
# =============================================================================

write_file "app/Services/Cms/Seo/SeoResolutionService.php" '<?php

declare(strict_types=1);

namespace App\Services\Cms\Seo;

use App\DTOs\Cms\Seo\ResolvedSeoDTO;
use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Enums\Cms\Seo\RobotsDirectiveEnum;

/**
 * Resolves a SeoMetaDTO into a locale-specific ResolvedSeoDTO.
 *
 * Responsibilities:
 * - Resolve localized fields (meta_title, meta_description, og_image) to scalar
 * - Generate hreflang alternates from slug map + FRONTEND_URL
 * - Apply environment-aware robots override (staging = noindex)
 * - Apply draft content robots override
 * - Merge og fields from meta fields when og fields are absent
 *
 * This service does NOT touch the database.
 */
final class SeoResolutionService
{
    public function __construct(
        private readonly CanonicalUrlService $canonicalUrlService,
    ) {}

    /**
     * Resolve SEO metadata for API response.
     *
     * @param SeoMetaDTO          $seo          Raw SEO metadata from storage
     * @param string              $locale        Requested locale
     * @param string              $fallback      Fallback locale
     * @param array<string,mixed> $slugMap       Localized slug map for alternates generation
     * @param string              $routePrefix   e.g. "" for root pages, "blog" for blog posts
     * @param bool                $isPublished   Whether content is published
     */
    public function resolve(
        SeoMetaDTO $seo,
        string $locale,
        string $fallback,
        array $slugMap,
        string $routePrefix = '"'"''"'"',
        bool $isPublished = true,
    ): ResolvedSeoDTO {
        // Draft content must NEVER be indexable
        $robots = $isPublished
            ? $this->applyEnvironmentRobots($seo->robots)
            : RobotsDirectiveEnum::forDraft();

        $metaTitle       = $this->resolveLocaleField($seo->metaTitle, $locale, $fallback);
        $metaDescription = $this->resolveLocaleField($seo->metaDescription, $locale, $fallback);

        // OG falls back to meta fields
        $ogTitle = $this->resolveLocaleField(
            $seo->ogTitle ?: $seo->metaTitle,
            $locale,
            $fallback,
        );
        $ogDescription = $this->resolveLocaleField(
            $seo->ogDescription ?: $seo->metaDescription,
            $locale,
            $fallback,
        );

        // OG image: may be locale map or scalar
        $ogImage = $this->resolveOgImage($seo->ogImage, $locale, $fallback);

        // Canonical: prefer explicit, else generate from primary slug
        $canonical = $seo->canonicalUrl
            ?? $this->canonicalUrlService->generateCanonical($slugMap, $fallback, $routePrefix);

        // Alternates for hreflang
        $alternates = $this->canonicalUrlService->generateAlternates($slugMap, $routePrefix);

        return new ResolvedSeoDTO(
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
            canonicalUrl: $canonical,
            ogImage: $ogImage,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            robots: $robots->value,
            isIndexable: $robots->isIndexable(),
            isFollowable: $robots->isFollowable(),
            twitterCard: $seo->twitterCard,
            alternates: $alternates,
            structuredData: $seo->structuredData,
        );
    }

    /**
     * Staging/preview environments must block indexing entirely.
     */
    private function applyEnvironmentRobots(RobotsDirectiveEnum $robots): RobotsDirectiveEnum
    {
        $env = config('"'"'app.env'"'"');

        if (in_array($env, ['"'"'staging'"'"', '"'"'testing'"'"', '"'"'local'"'"'], true)) {
            return RobotsDirectiveEnum::NOINDEX_NOFOLLOW;
        }

        return $robots;
    }

    /**
     * @param array<string, string|null>|string|null $value
     */
    private function resolveLocaleField(
        array|string|null $value,
        string $locale,
        string $fallback,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value !== '"'"''"'"' ? $value : null;
        }

        $resolved = $value[$locale] ?? $value[$fallback] ?? null;

        if (is_string($resolved) && $resolved !== '"'"''"'"') {
            return $resolved;
        }

        // Last resort: first non-empty value
        foreach ($value as $candidate) {
            if (is_string($candidate) && $candidate !== '"'"''"'"') {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, string|null>|string|null $ogImage
     */
    private function resolveOgImage(
        array|string|null $ogImage,
        string $locale,
        string $fallback,
    ): ?string {
        if ($ogImage === null) {
            return null;
        }

        if (is_string($ogImage)) {
            return $ogImage !== '"'"''"'"' ? $ogImage : null;
        }

        return $this->resolveLocaleField($ogImage, $locale, $fallback);
    }
}
'

write_file "app/Services/Cms/Seo/CanonicalUrlService.php" '<?php

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
        $this->frontendUrl      = rtrim((string) config('"'"'app.frontend_url'"'"', '"'"''"'"'), '"'"'/'"'"');
        $this->primaryLocale    = (string) config('"'"'app.locale'"'"', '"'"'en'"'"');
        $this->supportedLocales = (array) config('"'"'app.supported_locales'"'"', ['"'"'en'"'"', '"'"'ar'"'"']);
    }

    /**
     * Generate the canonical URL from the slug map.
     * Always uses primary locale slug without locale prefix.
     *
     * @param array<string, mixed> $slugMap
     */
    public function generateCanonical(
        array $slugMap,
        string $fallback = '"'"'en'"'"',
        string $routePrefix = '"'"''"'"',
    ): ?string {
        $slug = $slugMap[$this->primaryLocale]
            ?? $slugMap[$fallback]
            ?? null;

        if ($slug === null || $slug === '"'"''"'"') {
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
        string $routePrefix = '"'"''"'"',
    ): array {
        $alternates = [];

        foreach ($this->supportedLocales as $locale) {
            $slug = $slugMap[$locale] ?? $slugMap[$this->primaryLocale] ?? null;

            if ($slug === null || $slug === '"'"''"'"') {
                continue;
            }

            $alternates[$locale] = $this->buildUrl($locale, (string) $slug, $routePrefix);
        }

        // x-default points to primary locale
        if (isset($alternates[$this->primaryLocale])) {
            $alternates['"'"'x-default'"'"'] = $alternates[$this->primaryLocale];
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

        if ($routePrefix !== '"'"''"'"') {
            $segments[] = trim($routePrefix, '"'"'/'"'"');
        }

        $segments[] = trim($slug, '"'"'/'"'"');

        $path = implode('"'"'/'"'"', array_filter($segments));

        return $this->frontendUrl . '"'"'/'"'"' . $path;
    }
}
'

write_file "app/Services/Cms/Seo/StructuredDataService.php" '<?php

declare(strict_types=1);

namespace App\Services\Cms\Seo;

/**
 * Builds structured data (JSON-LD) payloads for CMS entities.
 *
 * Rules:
 * - Backend returns structured schema PAYLOADS only (PHP arrays).
 * - Frontend renders them as <script type="application/ld+json">.
 * - NO raw script tags stored in DB.
 * - NO Blade rendering.
 * - All schemas are locale-aware via resolved strings.
 *
 * Supported schemas (foundation — not all implemented):
 * - Organization        (platform-wide)
 * - WebSite            (platform-wide)
 * - Article            (blog posts)
 * - TechArticle        (documentation)
 * - BreadcrumbList     (any hierarchical content)
 * - FAQPage            (future)
 * - SoftwareApplication (future — pricing/features pages)
 */
final class StructuredDataService
{
    private string $frontendUrl;
    private string $appName;

    public function __construct()
    {
        $this->frontendUrl = rtrim((string) config('"'"'app.frontend_url'"'"', '"'"''"'"'), '"'"'/'"'"');
        $this->appName     = (string) config('"'"'app.name'"'"', '"'"''"'"');
    }

    /**
     * Organization schema — platform-wide, locale-independent.
     * Used on About, Home pages.
     *
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        return [
            '"'"'@context'"'"' => '"'"'https://schema.org'"'"',
            '"'"'@type'"'"'    => '"'"'Organization'"'"',
            '"'"'name'"'"'     => $this->appName,
            '"'"'url'"'"'      => $this->frontendUrl,
            '"'"'logo'"'"'     => [
                '"'"'@type'"'"' => '"'"'ImageObject'"'"',
                '"'"'url'"'"'   => $this->frontendUrl . '"'"'/logo.png'"'"',
            ],
            '"'"'sameAs'"'"'   => [],
        ];
    }

    /**
     * WebSite schema — for home page.
     * Enables Sitelinks Searchbox in Google.
     *
     * @return array<string, mixed>
     */
    public function website(): array
    {
        return [
            '"'"'@context'"'"'        => '"'"'https://schema.org'"'"',
            '"'"'@type'"'"'           => '"'"'WebSite'"'"',
            '"'"'name'"'"'            => $this->appName,
            '"'"'url'"'"'             => $this->frontendUrl,
            '"'"'potentialAction'"'"' => [
                '"'"'@type'"'"'       => '"'"'SearchAction'"'"',
                '"'"'target'"'"'      => [
                    '"'"'@type'"'"'       => '"'"'EntryPoint'"'"',
                    '"'"'urlTemplate'"'"' => $this->frontendUrl . '"'"'/search?q={search_term_string}'"'"',
                ],
                '"'"'query-input'"'"' => '"'"'required name=search_term_string'"'"',
            ],
        ];
    }

    /**
     * Article schema for blog posts.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function article(array $data): array
    {
        return [
            '"'"'@context'"'"'         => '"'"'https://schema.org'"'"',
            '"'"'@type'"'"'            => '"'"'Article'"'"',
            '"'"'headline'"'"'         => $data['"'"'title'"'"'] ?? '"'"''"'"',
            '"'"'description'"'"'      => $data['"'"'excerpt'"'"'] ?? '"'"''"'"',
            '"'"'image'"'"'            => $data['"'"'cover_image'"'"'] ?? null,
            '"'"'datePublished'"'"'    => $data['"'"'published_at'"'"'] ?? null,
            '"'"'dateModified'"'"'     => $data['"'"'updated_at'"'"'] ?? null,
            '"'"'author'"'"'           => [
                '"'"'@type'"'"' => '"'"'Person'"'"',
                '"'"'name'"'"'  => $data['"'"'author_name'"'"'] ?? '"'"''"'"',
            ],
            '"'"'publisher'"'"'        => [
                '"'"'@type'"'"' => '"'"'Organization'"'"',
                '"'"'name'"'"'  => $this->appName,
                '"'"'logo'"'"'  => [
                    '"'"'@type'"'"' => '"'"'ImageObject'"'"',
                    '"'"'url'"'"'   => $this->frontendUrl . '"'"'/logo.png'"'"',
                ],
            ],
            '"'"'mainEntityOfPage'"'"' => [
                '"'"'@type'"'"' => '"'"'WebPage'"'"',
                '"'"'@id'"'"'   => $data['"'"'url'"'"'] ?? '"'"''"'"',
            ],
        ];
    }

    /**
     * TechArticle schema for documentation pages.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function techArticle(array $data): array
    {
        return [
            '"'"'@context'"'"'         => '"'"'https://schema.org'"'"',
            '"'"'@type'"'"'            => '"'"'TechArticle'"'"',
            '"'"'headline'"'"'         => $data['"'"'title'"'"'] ?? '"'"''"'"',
            '"'"'description'"'"'      => $data['"'"'excerpt'"'"'] ?? '"'"''"'"',
            '"'"'dateModified'"'"'     => $data['"'"'updated_at'"'"'] ?? null,
            '"'"'publisher'"'"'        => [
                '"'"'@type'"'"' => '"'"'Organization'"'"',
                '"'"'name'"'"'  => $this->appName,
            ],
            '"'"'mainEntityOfPage'"'"' => [
                '"'"'@type'"'"' => '"'"'WebPage'"'"',
                '"'"'@id'"'"'   => $data['"'"'url'"'"'] ?? '"'"''"'"',
            ],
        ];
    }

    /**
     * BreadcrumbList schema.
     *
     * @param array<int, array{name: string, url: string}> $items
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $items): array
    {
        $listItems = [];

        foreach ($items as $position => $item) {
            $listItems[] = [
                '"'"'@type'"'"'    => '"'"'ListItem'"'"',
                '"'"'position'"'"' => $position + 1,
                '"'"'name'"'"'     => $item['"'"'name'"'"'],
                '"'"'item'"'"'     => $item['"'"'url'"'"'],
            ];
        }

        return [
            '"'"'@context'"'"'        => '"'"'https://schema.org'"'"',
            '"'"'@type'"'"'           => '"'"'BreadcrumbList'"'"',
            '"'"'itemListElement'"'"' => $listItems,
        ];
    }
}
'

write_file "app/Services/Cms/Sitemap/MarketingSitemapService.php" '<?php

declare(strict_types=1);

namespace App\Services\Cms\Sitemap;

use App\DTOs\Cms\Sitemap\SitemapEntryDTO;
use App\Enums\Cms\Seo\SitemapChangefreqEnum;
use App\Repositories\Cms\Sitemap\SitemapRepository;
use App\Services\Cms\Seo\CanonicalUrlService;
use Illuminate\Support\Facades\Cache;

/**
 * Builds sitemap entries for all marketing CMS content types.
 *
 * Sitemap architecture:
 * - /sitemap.xml           → index pointing to sub-sitemaps
 * - /sitemap-marketing.xml → marketing pages (home, about, etc.)
 * - /sitemap-blog.xml      → published blog posts
 * - /sitemap-docs.xml      → published documentation (future)
 *
 * All entries are:
 * - Published only (never draft/scheduled)
 * - Locale-aware (alternates via hreflang)
 * - Cacheable (tag-based invalidation)
 */
final class MarketingSitemapService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly SitemapRepository  $sitemapRepository,
        private readonly CanonicalUrlService $canonicalUrlService,
    ) {}

    /**
     * Get marketing pages sitemap entries.
     *
     * @return SitemapEntryDTO[]
     */
    public function getMarketingEntries(): array
    {
        return Cache::tags(['"'"'cms:sitemap'"'"', '"'"'cms:sitemap:marketing'"'"'])
            ->remember(
                '"'"'sitemap:marketing'"'"',
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->buildMarketingEntries(),
            );
    }

    /**
     * Get blog posts sitemap entries.
     *
     * @return SitemapEntryDTO[]
     */
    public function getBlogEntries(): array
    {
        return Cache::tags(['"'"'cms:sitemap'"'"', '"'"'cms:sitemap:blog'"'"'])
            ->remember(
                '"'"'sitemap:blog'"'"',
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->buildBlogEntries(),
            );
    }

    /**
     * Invalidate ALL sitemap caches.
     * Called on any CMS publish/unpublish action.
     */
    public function invalidateAll(): void
    {
        Cache::tags(['"'"'cms:sitemap'"'"'])->flush();
    }

    /**
     * Invalidate marketing sitemap only.
     */
    public function invalidateMarketing(): void
    {
        Cache::tags(['"'"'cms:sitemap:marketing'"'"'])->flush();
    }

    /**
     * Invalidate blog sitemap only.
     */
    public function invalidateBlog(): void
    {
        Cache::tags(['"'"'cms:sitemap:blog'"'"'])->flush();
    }

    /**
     * @return SitemapEntryDTO[]
     */
    private function buildMarketingEntries(): array
    {
        $pages = $this->sitemapRepository->getPublishedMarketingPages();
        $entries = [];

        foreach ($pages as $page) {
            $slugMap = is_array($page->slug) ? $page->slug : [];

            $canonical  = $this->canonicalUrlService->generateCanonical($slugMap);
            $alternates = $this->canonicalUrlService->generateAlternates($slugMap);

            if ($canonical === null) {
                continue;
            }

            // Home page gets highest priority
            $priority = $page->type->value === '"'"'home'"'"' ? 1.0 : 0.8;

            $entries[] = new SitemapEntryDTO(
                loc: $canonical,
                lastmod: $page->updated_at,
                changefreq: SitemapChangefreqEnum::MONTHLY,
                priority: $priority,
                alternates: $alternates,
            );
        }

        return $entries;
    }

    /**
     * @return SitemapEntryDTO[]
     */
    private function buildBlogEntries(): array
    {
        $posts = $this->sitemapRepository->getPublishedBlogPosts();
        $entries = [];

        foreach ($posts as $post) {
            // Blog posts use translation rows for slugs
            $slugMap = [];
            foreach ($post->translations as $translation) {
                $slugMap[$translation->locale] = $translation->slug;
            }

            if (empty($slugMap)) {
                continue;
            }

            $canonical  = $this->canonicalUrlService->generateCanonical($slugMap, '"'"'en'"'"', '"'"'blog'"'"');
            $alternates = $this->canonicalUrlService->generateAlternates($slugMap, '"'"'blog'"'"');

            if ($canonical === null) {
                continue;
            }

            $entries[] = new SitemapEntryDTO(
                loc: $canonical,
                lastmod: $post->updated_at,
                changefreq: SitemapChangefreqEnum::WEEKLY,
                priority: 0.7,
                alternates: $alternates,
            );
        }

        return $entries;
    }
}
'

write_file "app/Services/Cms/Seo/IsrRevalidationService.php" '<?php

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
        $url    = config('"'"'services.nextjs.revalidation_url'"'"');
        $secret = config('"'"'services.nextjs.revalidation_secret'"'"');

        if (empty($url)) {
            return;
        }

        try {
            Http::timeout(5)
                ->post((string) $url, [
                    '"'"'secret'"'"' => $secret,
                    '"'"'paths'"'"'  => $paths,
                ]);
        } catch (\Throwable $e) {
            // Never fail publish because of revalidation errors
            Log::warning('"'"'ISR revalidation failed'"'"', [
                '"'"'paths'"'"' => $paths,
                '"'"'error'"'"' => $e->getMessage(),
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
    public function pathsFromSlugMap(array $slugMap, string $routePrefix = '"'"''"'"'): array
    {
        $primaryLocale = (string) config('"'"'app.locale'"'"', '"'"'en'"'"');
        $paths = [];

        foreach ($slugMap as $locale => $slug) {
            $segments = [];

            if ($locale !== $primaryLocale) {
                $segments[] = $locale;
            }

            if ($routePrefix !== '"'"''"'"') {
                $segments[] = trim($routePrefix, '"'"'/'"'"');
            }

            $segments[] = trim((string) $slug, '"'"'/'"'"');

            $paths[] = '"'"'/'"'"' . implode('"'"'/'"'"', array_filter($segments));
        }

        return $paths;
    }
}
'

echo "▶ Creating Repository..."

# =============================================================================
# REPOSITORY
# =============================================================================

write_file "app/Repositories/Cms/Sitemap/SitemapRepository.php" '<?php

declare(strict_types=1);

namespace App\Repositories\Cms\Sitemap;

use App\Models\BlogPost;
use App\Models\Cms\MarketingPage;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for sitemap data retrieval.
 *
 * Rules:
 * - Published content only (never draft or scheduled future)
 * - Minimal column selection for performance
 * - Eager load only what sitemap needs
 *
 * This is intentionally separate from content repositories
 * to keep sitemap queries lean and independent of pagination.
 */
final class SitemapRepository
{
    /**
     * Get all published marketing pages for sitemap.
     * Minimal columns only — no sections, no seo blob.
     */
    public function getPublishedMarketingPages(): Collection
    {
        return MarketingPage::query()
            ->select(['"'"'id'"'"', '"'"'type'"'"', '"'"'slug'"'"', '"'"'status'"'"', '"'"'published_at'"'"', '"'"'updated_at'"'"'])
            ->published()
            ->orderBy('"'"'type'"'"')
            ->get();
    }

    /**
     * Get all published blog posts for sitemap.
     * Loads only translations needed for slug maps.
     */
    public function getPublishedBlogPosts(): Collection
    {
        return BlogPost::query()
            ->select(['"'"'id'"'"', '"'"'is_published'"'"', '"'"'published_at'"'"', '"'"'updated_at'"'"'])
            ->published()
            ->with([
                '"'"'translations'"'"' => fn ($query) => $query->select([
                    '"'"'blog_post_id'"'"',
                    '"'"'locale'"'"',
                    '"'"'slug'"'"',
                ]),
            ])
            ->orderByDesc('"'"'published_at'"'"')
            ->get();
    }
}
'

echo "▶ Creating API Resources..."

# =============================================================================
# API RESOURCES
# =============================================================================

write_file "app/Http/Resources/Cms/Seo/SeoResource.php" '<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Seo;

use App\DTOs\Cms\Seo\ResolvedSeoDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes the resolved SEO payload for API consumers (Next.js).
 *
 * Input: ResolvedSeoDTO (all scalar — no locale maps)
 * Output: Flat JSON object ready for generateMetadata()
 *
 * Contract:
 * {
 *   "meta_title": "...",
 *   "meta_description": "...",
 *   "canonical_url": "https://...",
 *   "og": {
 *     "title": "...",
 *     "description": "...",
 *     "image": "..."
 *   },
 *   "twitter": {
 *     "card": "summary_large_image"
 *   },
 *   "robots": "index,follow",
 *   "is_indexable": true,
 *   "is_followable": true,
 *   "alternates": {
 *     "en": "https://example.com/about",
 *     "ar": "https://example.com/ar/من-نحن",
 *     "x-default": "https://example.com/about"
 *   },
 *   "structured_data": null
 * }
 *
 * @mixin ResolvedSeoDTO
 */
class SeoResource extends JsonResource
{
    /**
     * @param ResolvedSeoDTO $resource
     */
    public function __construct(ResolvedSeoDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var ResolvedSeoDTO $seo */
        $seo = $this->resource;

        return [
            '"'"'meta_title'"'"'       => $seo->metaTitle,
            '"'"'meta_description'"'"' => $seo->metaDescription,
            '"'"'canonical_url'"'"'    => $seo->canonicalUrl,
            '"'"'og'"'"' => [
                '"'"'title'"'"'       => $seo->ogTitle ?? $seo->metaTitle,
                '"'"'description'"'"' => $seo->ogDescription ?? $seo->metaDescription,
                '"'"'image'"'"'       => $seo->ogImage,
            ],
            '"'"'twitter'"'"' => [
                '"'"'card'"'"' => $seo->twitterCard,
            ],
            '"'"'robots'"'"'          => $seo->robots,
            '"'"'is_indexable'"'"'    => $seo->isIndexable,
            '"'"'is_followable'"'"'   => $seo->isFollowable,
            '"'"'alternates'"'"'      => $seo->alternates,
            '"'"'structured_data'"'"' => $seo->structuredData,
        ];
    }
}
'

write_file "app/Http/Resources/Cms/Sitemap/SitemapResource.php" '<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Sitemap;

use App\DTOs\Cms\Sitemap\SitemapEntryDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a single sitemap entry for JSON API response.
 * Next.js consumes this to generate its own sitemap.ts.
 *
 * @mixin SitemapEntryDTO
 */
class SitemapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var SitemapEntryDTO $entry */
        $entry = $this->resource;

        return $entry->toArray();
    }
}
'

echo "▶ Creating Controllers..."

# =============================================================================
# CONTROLLERS
# =============================================================================

write_file "app/Http/Controllers/Api/Cms/SitemapController.php" '<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\Sitemap\SitemapResource;
use App\Services\Cms\Sitemap\MarketingSitemapService;
use Illuminate\Http\JsonResponse;

/**
 * Serves sitemap data as JSON for Next.js sitemap generation.
 *
 * Next.js App Router consumes these endpoints in its
 * app/sitemap.ts file to generate XML sitemaps for search engines.
 *
 * Endpoints:
 *   GET /api/v1/cms/sitemap/marketing   → marketing pages entries
 *   GET /api/v1/cms/sitemap/blog        → blog post entries
 *
 * These endpoints are public, cached, and have no auth requirement.
 * They never expose draft content.
 */
class SitemapController extends Controller
{
    public function __construct(
        private readonly MarketingSitemapService $sitemapService,
    ) {}

    public function marketing(): JsonResponse
    {
        $entries = $this->sitemapService->getMarketingEntries();

        return $this->success(
            SitemapResource::collection(collect($entries)),
            __('cms.sitemap_generated'),
        );
    }

    public function blog(): JsonResponse
    {
        $entries = $this->sitemapService->getBlogEntries();

        return $this->success(
            SitemapResource::collection(collect($entries)),
            __('cms.sitemap_generated'),
        );
    }
}
'

echo "▶ Updating MarketingPageCacheService with tag support..."

# =============================================================================
# UPDATE: MarketingPageCacheService — add tag support
# =============================================================================

write_file "app/Services/Cms/MarketingPageCacheService.php" '<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\Cms\MarketingPage;
use App\Services\Cms\Sitemap\MarketingSitemapService;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache service for marketing page content.
 *
 * Updated to use tag-based cache for reliable invalidation.
 *
 * Tags strategy:
 * - All marketing pages: cms:marketing
 * - Per-page: cms:marketing:{type}
 *
 * Sitemap invalidation is triggered on every page cache bust
 * because publishing a page always changes sitemap content.
 */
class MarketingPageCacheService
{
    private const TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly MarketingSitemapService $sitemapService,
    ) {}

    public function remember(string $locale, string $slug, Closure $callback): MarketingPage
    {
        /** @var MarketingPage $page */
        $page = Cache::tags($this->pageTags())
            ->remember(
                $this->key($locale, $slug),
                self::TTL_SECONDS,
                $callback,
            );

        return $page;
    }

    public function invalidateForPage(MarketingPage $page, array $additionalSlugs = []): void
    {
        // Flush all entries tagged with this page type
        Cache::tags($this->pageTypeTags($page))->flush();

        // Also flush by key for each locale+slug combination
        $pageSlugs = is_array($page->slug) ? array_values($page->slug) : [];
        $locales   = config('"'"'content.editable_locales'"'"', ['"'"'en'"'"', '"'"'ar'"'"']);

        $slugs = array_values(array_unique(array_filter([
            ...$pageSlugs,
            ...$additionalSlugs,
        ])));

        foreach (is_array($locales) ? $locales : ['"'"'en'"'"', '"'"'ar'"'"'] as $locale) {
            foreach ($slugs as $slug) {
                Cache::tags($this->pageTags())
                    ->forget($this->key((string) $locale, (string) $slug));
            }
        }

        // Publishing always changes sitemap
        $this->sitemapService->invalidateMarketing();
    }

    /**
     * Flush all marketing page cache entries.
     */
    public function invalidateAll(): void
    {
        Cache::tags($this->pageTags())->flush();
        $this->sitemapService->invalidateMarketing();
    }

    private function key(string $locale, string $slug): string
    {
        return sprintf('"'"'cms:page:%s:%s'"'"', $locale, $slug);
    }

    /**
     * @return string[]
     */
    private function pageTags(): array
    {
        return ['"'"'cms:marketing'"'"'];
    }

    /**
     * @return string[]
     */
    private function pageTypeTags(MarketingPage $page): array
    {
        return [
            '"'"'cms:marketing'"'"',
            '"'"'cms:marketing:'"'"' . $page->type->value,
        ];
    }
}
'

echo "▶ Updating PublishMarketingPageAction with ISR + sitemap invalidation..."

# =============================================================================
# UPDATE: PublishMarketingPageAction — add ISR revalidation
# =============================================================================

write_file "app/Actions/Cms/MarketingPage/Admin/PublishMarketingPageAction.php" '<?php

declare(strict_types=1);

namespace App\Actions\Cms\MarketingPage\Admin;

use App\DTOs\Cms\MarketingPage\Admin\PublishMarketingPageDTO;
use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Models\Cms\MarketingPage;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;
use App\Services\Cms\Seo\IsrRevalidationService;
use Illuminate\Support\Facades\DB;

class PublishMarketingPageAction
{
    public function __construct(
        private readonly MarketingPageRepository $repository,
        private readonly MarketingPageCacheService $cacheService,
        private readonly IsrRevalidationService $isrService,
    ) {}

    public function execute(PublishMarketingPageDTO $dto): MarketingPage
    {
        $page = $this->repository->findByIdOrFail($dto->id);

        $page = DB::transaction(function () use ($page, $dto): MarketingPage {
            return $this->repository->update($page, [
                '"'"'status'"'"'       => MarketingPageStatusEnum::PUBLISHED->value,
                '"'"'published_at'"'"' => $dto->publishedAt ?: now()->toDateTimeString(),
                '"'"'updated_by'"'"'   => $dto->updatedBy,
            ]);
        });

        // Invalidate page content cache + sitemap cache
        $this->cacheService->invalidateForPage($page);

        // Trigger Next.js ISR revalidation (non-blocking, never throws)
        $slugMap = is_array($page->slug) ? $page->slug : [];
        $paths   = $this->isrService->pathsFromSlugMap($slugMap);
        $this->isrService->revalidatePaths($paths);

        return $page;
    }
}
'

echo "▶ Updating MarketingPageResource with full SEO payload..."

# =============================================================================
# UPDATE: MarketingPageResource — integrate SeoResolutionService
# =============================================================================

write_file "app/Http/Resources/Cms/MarketingPage/MarketingPageResource.php" '<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\MarketingPage;

use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Http\Resources\Cms\Seo\SeoResource;
use App\Models\Cms\MarketingPage;
use App\Services\Cms\LocalizedContentResolver;
use App\Services\Cms\Seo\SeoResolutionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MarketingPage */
class MarketingPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MarketingPage $page */
        $page = $this->resource;

        /** @var LocalizedContentResolver $resolver */
        $resolver = app(LocalizedContentResolver::class);

        /** @var SeoResolutionService $seoService */
        $seoService = app(SeoResolutionService::class);

        $locale         = (string) ($request->query('"'"'locale'"'"') ?: config('"'"'content.default_locale'"'"', '"'"'en'"'"'));
        $fallbackLocale = (string) config('"'"'content.default_locale'"'"', '"'"'en'"'"');

        $seoArray = is_array($page->seo) ? $page->seo : [];
        $seoMeta  = SeoMetaDTO::fromArray($seoArray);
        $slugMap  = is_array($page->slug) ? $page->slug : [];

        $isPublished = $page->status === \App\Enums\Cms\MarketingPage\MarketingPageStatusEnum::PUBLISHED
            && ($page->published_at === null || $page->published_at->isPast());

        $resolvedSeo = $seoService->resolve(
            seo: $seoMeta,
            locale: $locale,
            fallback: $fallbackLocale,
            slugMap: $slugMap,
            routePrefix: '"'"''"'"',
            isPublished: $isPublished,
        );

        return [
            '"'"'type'"'"'     => $page->type->value,
            '"'"'slug'"'"'     => $resolver->resolveLocalizedField($slugMap, $locale, $fallbackLocale),
            '"'"'title'"'"'    => $resolver->resolveLocalizedField($page->title, $locale, $fallbackLocale),
            '"'"'sections'"'"' => $resolver->resolveLocalizedPayload($page->sections, $locale, $fallbackLocale),
            '"'"'seo'"'"'      => new SeoResource($resolvedSeo),
        ];
    }
}
'

echo "▶ Adding lang keys..."

# =============================================================================
# LANG
# =============================================================================

# Read existing lang/en/cms.php and append if key missing
CMS_EN="lang/en/cms.php"
CMS_AR="lang/ar/cms.php"

# We write complete replacements to be safe (append-safe pattern)
# Read current content and check if sitemap_generated exists
if ! grep -q "sitemap_generated" "$CMS_EN" 2>/dev/null; then
    # Append before closing bracket
    TMPFILE=$(mktemp)
    head -n -1 "$CMS_EN" > "$TMPFILE"
    echo "" >> "$TMPFILE"
    echo "    // SEO / Sitemap" >> "$TMPFILE"
    echo "    'sitemap_generated' => 'Sitemap data generated successfully.'," >> "$TMPFILE"
    echo "    'seo_resolved'      => 'SEO metadata resolved.'," >> "$TMPFILE"
    echo "];" >> "$TMPFILE"
    mv "$TMPFILE" "$CMS_EN"
    echo "  ✅ lang/en/cms.php updated"
fi

if ! grep -q "sitemap_generated" "$CMS_AR" 2>/dev/null; then
    TMPFILE=$(mktemp)
    head -n -1 "$CMS_AR" > "$TMPFILE"
    echo "" >> "$TMPFILE"
    echo "    // SEO / Sitemap" >> "$TMPFILE"
    echo "    'sitemap_generated' => 'تم توليد بيانات الخريطة بنجاح.'," >> "$TMPFILE"
    echo "    'seo_resolved'      => 'تم حل بيانات SEO.'," >> "$TMPFILE"
    echo "];" >> "$TMPFILE"
    mv "$TMPFILE" "$CMS_AR"
    echo "  ✅ lang/ar/cms.php updated"
fi

echo "▶ Adding config/services.php nextjs section..."

# =============================================================================
# CONFIG — services.php
# =============================================================================

SERVICES_CONFIG="config/services.php"
if ! grep -q "nextjs" "$SERVICES_CONFIG" 2>/dev/null; then
    TMPFILE=$(mktemp)
    head -n -1 "$SERVICES_CONFIG" > "$TMPFILE"
    echo "" >> "$TMPFILE"
    echo "    /*" >> "$TMPFILE"
    echo "    |--------------------------------------------------------------------------" >> "$TMPFILE"
    echo "    | Next.js ISR Revalidation" >> "$TMPFILE"
    echo "    |--------------------------------------------------------------------------" >> "$TMPFILE"
    echo "    |" >> "$TMPFILE"
    echo "    | Configuration for on-demand ISR revalidation webhook." >> "$TMPFILE"
    echo "    | Set FRONTEND_REVALIDATION_URL and FRONTEND_REVALIDATION_SECRET in .env" >> "$TMPFILE"
    echo "    |" >> "$TMPFILE"
    echo "    */" >> "$TMPFILE"
    echo "    'nextjs' => [" >> "$TMPFILE"
    echo "        'revalidation_url'    => env('FRONTEND_REVALIDATION_URL')," >> "$TMPFILE"
    echo "        'revalidation_secret' => env('FRONTEND_REVALIDATION_SECRET')," >> "$TMPFILE"
    echo "    ]," >> "$TMPFILE"
    echo "];" >> "$TMPFILE"
    mv "$TMPFILE" "$SERVICES_CONFIG"
    echo "  ✅ config/services.php updated"
fi

echo "▶ Creating sitemap routes..."

# =============================================================================
# ROUTES
# =============================================================================

write_file "routes/api/v1/public/sitemap.php" '<?php

use App\Http\Controllers\Api\Cms\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitemap Routes
|--------------------------------------------------------------------------
|
| These routes serve structured sitemap data as JSON for Next.js.
| Next.js consumes these in its sitemap.ts file to generate XML sitemaps.
|
| All endpoints are:
| - Public (no auth required)
| - Cached (tag-based cache, invalidated on publish)
| - Published-only (never expose draft content)
|
*/

Route::prefix('"'"'v1/cms/sitemap'"'"')
    ->controller(SitemapController::class)
    ->group(function (): void {
        Route::get('"'"'/marketing'"'"', '"'"'marketing'"'"')->name('"'"'cms.sitemap.marketing'"'"');
        Route::get('"'"'/blog'"'"', '"'"'blog'"'"')->name('"'"'cms.sitemap.blog'"'"');
    });
'

echo "▶ Creating migration for marketing_pages seo column normalization..."

# =============================================================================
# MIGRATION — normalize seo JSON schema (non-destructive)
# =============================================================================

TIMESTAMP=$(date +%Y_%m_%d_%H%M%S)

write_file "database/migrations/${TIMESTAMP}_normalize_marketing_pages_seo_schema.php" '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Non-destructive migration: normalize the seo JSON column in marketing_pages.
 *
 * The seo column already exists as JSON.
 * This migration backfills any existing rows with the full
 * normalized SEO schema structure so the application layer
 * can safely access all expected keys.
 *
 * New schema enforced by application layer (SeoMetaDTO):
 * {
 *   "meta_title":       {"en": null, "ar": null},
 *   "meta_description": {"en": null, "ar": null},
 *   "canonical_url":    null,
 *   "og_image":         null,
 *   "og_title":         {"en": null, "ar": null},
 *   "og_description":   {"en": null, "ar": null},
 *   "robots":           "index,follow",
 *   "twitter_card":     "summary_large_image",
 *   "structured_data":  null
 * }
 *
 * NO columns are added or removed.
 * NO data is destroyed.
 * Existing values are preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pages = DB::table('"'"'marketing_pages'"'"')
            ->whereNull('"'"'deleted_at'"'"')
            ->get(['"'"'id'"'"', '"'"'seo'"'"']);

        $defaults = [
            '"'"'meta_title'"'"'       => ['"'"'en'"'"' => null, '"'"'ar'"'"' => null],
            '"'"'meta_description'"'"' => ['"'"'en'"'"' => null, '"'"'ar'"'"' => null],
            '"'"'canonical_url'"'"'    => null,
            '"'"'og_image'"'"'         => null,
            '"'"'og_title'"'"'         => ['"'"'en'"'"' => null, '"'"'ar'"'"' => null],
            '"'"'og_description'"'"'   => ['"'"'en'"'"' => null, '"'"'ar'"'"' => null],
            '"'"'robots'"'"'           => '"'"'index,follow'"'"',
            '"'"'twitter_card'"'"'     => '"'"'summary_large_image'"'"',
            '"'"'structured_data'"'"'  => null,
        ];

        foreach ($pages as $page) {
            $existing = json_decode((string) $page->seo, true) ?? [];

            // Merge: existing values win, defaults fill missing keys
            $normalized = array_merge($defaults, array_filter(
                $existing,
                fn ($value) => $value !== null && $value !== '"'"''"'"' && $value !== [],
            ));

            // Ensure nested locale maps are preserved
            foreach (['"'"'meta_title'"'"', '"'"'meta_description'"'"', '"'"'og_title'"'"', '"'"'og_description'"'"'] as $field) {
                if (isset($existing[$field])) {
                    // Preserve existing locale maps
                    $normalized[$field] = is_array($existing[$field])
                        ? array_merge($defaults[$field], $existing[$field])
                        : $existing[$field];
                }
            }

            DB::table('"'"'marketing_pages'"'"')
                ->where('"'"'id'"'"', $page->id)
                ->update(['"'"'seo'"'"' => json_encode($normalized)]);
        }
    }

    public function down(): void
    {
        // Non-destructive — no rollback needed
        // Original data is preserved within the normalized structure
    }
};
'

echo "▶ Registering sitemap routes in api.php..."

# =============================================================================
# UPDATE: routes/api.php — register sitemap routes
# =============================================================================

API_ROUTES="routes/api.php"
if ! grep -q "sitemap" "$API_ROUTES" 2>/dev/null; then
    TMPFILE=$(mktemp)
    # Find the last require line and add after it
    awk '
    /require __DIR__/ { print; found=1; next }
    found && /;/ && !done {
        print;
        print "require __DIR__ . '"'"'/api/v1/public/sitemap.php'"'"';";
        done=1;
        next
    }
    { print }
    ' "$API_ROUTES" > "$TMPFILE"
    mv "$TMPFILE" "$API_ROUTES"
    echo "  ✅ routes/api.php updated with sitemap routes"
else
    echo "  ⏭  routes/api.php already has sitemap routes"
fi

echo ""
echo "============================================================"
echo " ✅ SEO Infrastructure Foundation — COMPLETE"
echo "============================================================"
echo ""
echo "📁 New Files Created:"
echo "   app/Enums/Cms/Seo/RobotsDirectiveEnum.php"
echo "   app/Enums/Cms/Seo/SitemapChangefreqEnum.php"
echo "   app/DTOs/Cms/Seo/SeoMetaDTO.php"
echo "   app/DTOs/Cms/Seo/ResolvedSeoDTO.php"
echo "   app/DTOs/Cms/Sitemap/SitemapEntryDTO.php"
echo "   app/Services/Cms/Seo/SeoResolutionService.php"
echo "   app/Services/Cms/Seo/CanonicalUrlService.php"
echo "   app/Services/Cms/Seo/StructuredDataService.php"
echo "   app/Services/Cms/Seo/IsrRevalidationService.php"
echo "   app/Services/Cms/Sitemap/MarketingSitemapService.php"
echo "   app/Repositories/Cms/Sitemap/SitemapRepository.php"
echo "   app/Http/Resources/Cms/Seo/SeoResource.php"
echo "   app/Http/Resources/Cms/Sitemap/SitemapResource.php"
echo "   app/Http/Controllers/Api/Cms/SitemapController.php"
echo "   routes/api/v1/public/sitemap.php"
echo "   database/migrations/${TIMESTAMP}_normalize_marketing_pages_seo_schema.php"
echo ""
echo "📝 Updated Files:"
echo "   app/Services/Cms/MarketingPageCacheService.php (tag-based cache)"
echo "   app/Actions/Cms/MarketingPage/Admin/PublishMarketingPageAction.php (ISR)"
echo "   app/Http/Resources/Cms/MarketingPage/MarketingPageResource.php (full SEO)"
echo "   config/services.php (nextjs revalidation)"
echo "   lang/en/cms.php"
echo "   lang/ar/cms.php"
echo "   routes/api.php"
echo ""
echo "🔧 Run migration:"
echo "   php artisan migrate"
echo ""
echo "🌐 New API Endpoints:"
echo "   GET /api/v1/cms/sitemap/marketing"
echo "   GET /api/v1/cms/sitemap/blog"
echo ""
echo "📖 Frontend Integration:"
echo "   SEO payload in page response: response.seo"
echo "   generateMetadata() consumes: meta_title, canonical_url, og, alternates"
echo "   Sitemap.ts fetches: /api/v1/cms/sitemap/marketing + /api/v1/cms/sitemap/blog"
echo ""