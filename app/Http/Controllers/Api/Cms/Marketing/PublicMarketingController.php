<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cms\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\MarketingPage\MarketingPageResource;
use App\Repositories\Cms\Marketing\Platform\PlatformMarketingPageRepository;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
            // 1. Try resolving from platform marketing pages (New Source of Truth)
            $page = $this->platformRepository->findPublishedBySlugOrFail($locale, $fallback, $slug);
            
            return $this->success(new MarketingPageResource($page));
        } catch (\Exception $e) {
            // 2. Fallback to legacy marketing pages (Temporary Bridge)
            try {
                $page = $this->legacyRepository->findPublishedBySlugOrFail($locale, $fallback, $slug);
                
                Log::info("CMS Migration: Resolved platform page from legacy fallback", [
                    'slug' => $slug,
                    'locale' => $locale
                ]);

                return $this->success(new MarketingPageResource($page));
            } catch (\Exception $legacyException) {
                // 3. Not found in either
                throw $legacyException;
            }
        }
    }
}
