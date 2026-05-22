<?php

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
        $this->frontendUrl = rtrim((string) config('app.frontend_url', ''), '/');
        $this->appName     = (string) config('app.name', '');
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
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $this->appName,
            'url'      => $this->frontendUrl,
            'logo'     => [
                '@type' => 'ImageObject',
                'url'   => $this->frontendUrl . '/logo.png',
            ],
            'sameAs'   => [],
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
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => $this->appName,
            'url'             => $this->frontendUrl,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $this->frontendUrl . '/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
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
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => $data['title'] ?? '',
            'description'      => $data['excerpt'] ?? '',
            'image'            => $data['cover_image'] ?? null,
            'datePublished'    => $data['published_at'] ?? null,
            'dateModified'     => $data['updated_at'] ?? null,
            'author'           => [
                '@type' => 'Person',
                'name'  => $data['author_name'] ?? '',
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => $this->appName,
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => $this->frontendUrl . '/logo.png',
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $data['url'] ?? '',
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
            '@context'         => 'https://schema.org',
            '@type'            => 'TechArticle',
            'headline'         => $data['title'] ?? '',
            'description'      => $data['excerpt'] ?? '',
            'dateModified'     => $data['updated_at'] ?? null,
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => $this->appName,
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $data['url'] ?? '',
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
                '@type'    => 'ListItem',
                'position' => $position + 1,
                'name'     => $item['name'],
                'item'     => $item['url'],
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];
    }
}
