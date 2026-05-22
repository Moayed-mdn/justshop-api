<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cms\Seo;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\Sitemap\SitemapResource;
use App\Services\Cms\Seo\SitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Serves sitemap and SEO metadata as JSON for Next.js.
 */
class PublicCmsSeoController extends Controller
{
    public function __construct(
        private readonly SitemapService $sitemapService,
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

    public function docs(): JsonResponse
    {
        $entries = $this->sitemapService->getDocsEntries();

        return $this->success(
            SitemapResource::collection(collect($entries)),
            __('cms.sitemap_generated'),
        );
    }

    /**
     * Returns robots.txt directives based on environment.
     */
    public function robots(): Response
    {
        $env = config('app.env');
        $frontendUrl = rtrim((string) config('app.frontend_url', ''), '/');

        if (in_array($env, ['staging', 'testing', 'local'], true)) {
            $content = "User-agent: *\nDisallow: /";
        } else {
            $content = "User-agent: *\nAllow: /\n\nSitemap: {$frontendUrl}/sitemap.xml";
        }

        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
