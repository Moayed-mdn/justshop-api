<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Platform\Admin;

use App\DTOs\Cms\Marketing\Platform\Admin\DeletePlatformMarketingPageDTO;
use App\Repositories\Cms\Marketing\Platform\PlatformMarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;

class DeletePlatformMarketingPageAction
{
    public function __construct(
        private PlatformMarketingPageRepository $repository,
        private MarketingPageCacheService $cacheService,
    ) {}

    public function execute(DeletePlatformMarketingPageDTO $dto): void
    {
        $page = $this->repository->findByIdOrFail($dto->id);

        $this->repository->delete($page);
        $this->cacheService->invalidateAll();
    }
}
