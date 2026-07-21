<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\MarketingPage\MarketingPageResource;
use App\Exceptions\NotFoundException;
use App\Repositories\Cms\Marketing\Platform\PlatformMarketingPageRepository;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PublicMarketingController extends Controller
{
    public function __construct(
        private readonly PlatformMarketingPageRepository $platformRepository,
        private readonly MarketingPageRepository $legacyRepository
    ) {}

    public function show(string $slug): JsonResponse
    {
        $locale = app()->getLocale();
        $fallback = (string) config('content.default_locale', 'en');

        try {
            $page = $this->platformRepository->findPublishedBySlugOrFail($locale, $fallback, $slug);

            return $this->success(new MarketingPageResource($page));
        } catch (NotFoundException $platformNotFound) {
            if (!Schema::hasTable('marketing_pages')) {
                throw $platformNotFound;
            }

            $page = $this->legacyRepository->findPublishedBySlugOrFail($locale, $fallback, $slug);

            Log::info('CMS Migration: resolved marketing page from legacy fallback', [
                'slug' => $slug,
                'locale' => $locale,
            ]);

            return $this->success(new MarketingPageResource($page));
        }
    }
}
