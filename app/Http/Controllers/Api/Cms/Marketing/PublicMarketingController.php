<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cms\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\MarketingPage\MarketingPageResource;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use Illuminate\Http\JsonResponse;

class PublicMarketingController extends Controller
{
    public function __construct(
        private readonly MarketingPageRepository $repository
    ) {}

    public function show(string $slug): JsonResponse
    {
        $locale = app()->getLocale();
        $fallback = config('content.default_locale', 'en');

        $page = $this->repository->findPublishedBySlugOrFail($locale, $fallback, $slug);

        return $this->success(new MarketingPageResource($page));
    }
}
