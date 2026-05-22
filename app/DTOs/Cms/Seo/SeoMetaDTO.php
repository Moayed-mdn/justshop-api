<?php

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
            metaTitle: self::normalizeLocaleMap($seo['meta_title'] ?? $seo['title'] ?? []),
            metaDescription: self::normalizeLocaleMap($seo['meta_description'] ?? $seo['description'] ?? []),
            canonicalUrl: isset($seo['canonical_url']) && is_string($seo['canonical_url'])
                ? $seo['canonical_url']
                : null,
            ogImage: $seo['og_image'] ?? null,
            robots: isset($seo['robots']) && is_string($seo['robots'])
                ? (RobotsDirectiveEnum::tryFrom($seo['robots']) ?? RobotsDirectiveEnum::default())
                : RobotsDirectiveEnum::default(),
            structuredData: isset($seo['structured_data']) && is_array($seo['structured_data'])
                ? $seo['structured_data']
                : null,
            ogTitle: self::normalizeLocaleMap($seo['og_title'] ?? []),
            ogDescription: self::normalizeLocaleMap($seo['og_description'] ?? []),
            twitterCard: isset($seo['twitter_card']) && is_string($seo['twitter_card'])
                ? $seo['twitter_card']
                : 'summary_large_image',
        );
    }

    public static function fromTranslationRows(array $localeMap): self
    {
        $metaTitle       = [];
        $metaDescription = [];
        $ogImage         = [];
        $ogTitle         = [];
        $ogDescription   = [];
        $robots          = RobotsDirectiveEnum::default();
        $canonicalUrl    = null;
        $twitterCard     = 'summary_large_image';

        foreach ($localeMap as $locale => $fields) {
            if (!is_array($fields)) {
                continue;
            }
            $metaTitle[$locale]       = $fields['meta_title'] ?? null;
            $metaDescription[$locale] = $fields['meta_description'] ?? null;
            $ogImage[$locale]         = $fields['og_image'] ?? null;
            $ogTitle[$locale]         = $fields['og_title'] ?? $fields['meta_title'] ?? null;
            $ogDescription[$locale]   = $fields['og_description'] ?? $fields['meta_description'] ?? null;

            // canonical is shared; take from any locale (prefer en)
            if ($locale === 'en' && isset($fields['canonical_url'])) {
                $canonicalUrl = $fields['canonical_url'];
            }
            if ($canonicalUrl === null && isset($fields['canonical_url'])) {
                $canonicalUrl = $fields['canonical_url'];
            }

            // robots: use en, else first defined
            if ($locale === 'en' && isset($fields['robots'])) {
                $robots = RobotsDirectiveEnum::tryFrom($fields['robots']) ?? RobotsDirectiveEnum::default();
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
            'meta_title'       => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'canonical_url'    => $this->canonicalUrl,
            'og_image'         => $this->ogImage,
            'og_title'         => $this->ogTitle,
            'og_description'   => $this->ogDescription,
            'robots'           => $this->robots->value,
            'twitter_card'     => $this->twitterCard,
            'structured_data'  => $this->structuredData,
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
            return ['en' => $value];
        }
        return [];
    }
}
