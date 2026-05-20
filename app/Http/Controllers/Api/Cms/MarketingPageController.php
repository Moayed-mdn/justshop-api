<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cms;

use App\Actions\Cms\MarketingPage\ResolveMarketingPageBySlugAction;
use App\DTOs\Cms\MarketingPage\ResolveMarketingPageBySlugDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\MarketingPage\GetMarketingPageRequest;
use App\Http\Resources\Cms\MarketingPage\MarketingPageResource;
use Symfony\Component\HttpFoundation\JsonResponse;

class MarketingPageController extends Controller
{
    public function __construct(
        private ResolveMarketingPageBySlugAction $resolveMarketingPageBySlugAction,
    ) {}

    public function show(
        GetMarketingPageRequest $request,
        string $slug,
    ): JsonResponse {
        $page = $this->resolveMarketingPageBySlugAction->execute(
            ResolveMarketingPageBySlugDTO::fromRequest($request, $slug)
        );

        return $this->success(new MarketingPageResource($page));
    }
}
