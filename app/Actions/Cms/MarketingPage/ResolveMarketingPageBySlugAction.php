<?php

declare(strict_types=1);

namespace App\Actions\Cms\MarketingPage;

use App\DTOs\Cms\MarketingPage\ResolveMarketingPageBySlugDTO;
use App\Models\Cms\MarketingPage;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;

class ResolveMarketingPageBySlugAction
{
    public function __construct(
        private MarketingPageRepository $repository,
        private MarketingPageCacheService $cacheService,
    ) {}

    public function execute(ResolveMarketingPageBySlugDTO $dto): MarketingPage
    {
        return $this->cacheService->remember(
            $dto->locale,
            $dto->slug,
            fn (): MarketingPage => $this->repository->findPublishedBySlugOrFail(
                $dto->locale,
                $dto->fallbackLocale,
                $dto->slug,
            ),
        );
    }
}
