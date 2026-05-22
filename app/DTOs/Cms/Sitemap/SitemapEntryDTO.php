<?php

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
            'loc'        => $this->loc,
            'lastmod'    => $this->lastmod?->toAtomString(),
            'changefreq' => $this->changefreq->value,
            'priority'   => $this->priority,
            'alternates' => $this->alternates,
        ];
    }
}
