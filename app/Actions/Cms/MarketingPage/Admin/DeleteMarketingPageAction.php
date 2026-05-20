<?php

declare(strict_types=1);

namespace App\Actions\Cms\MarketingPage\Admin;

use App\DTOs\Cms\MarketingPage\Admin\DeleteMarketingPageDTO;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;

class DeleteMarketingPageAction
{
    public function __construct(
        private MarketingPageRepository $repository,
        private MarketingPageCacheService $cacheService,
    ) {}

    public function execute(DeleteMarketingPageDTO $dto): void
    {
        $page = $this->repository->findByIdOrFail($dto->id);

        $this->repository->delete($page);
        $this->cacheService->invalidateForPage($page);
    }
}
