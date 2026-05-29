<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Store\Admin;

use App\DTOs\Cms\Marketing\Store\Admin\DeleteStoreMarketingPageDTO;
use App\Repositories\Cms\Marketing\Store\StoreMarketingPageRepository;
use App\Services\Storefront\Runtime\RuntimeCacheService;

class DeleteStoreMarketingPageAction
{
    public function __construct(
        private readonly StoreMarketingPageRepository $repository,
        private readonly RuntimeCacheService $runtimeCacheService,
    ) {}

    public function execute(DeleteStoreMarketingPageDTO $dto): void
    {
        $page = $this->repository->findByIdOrFail($dto->storeId, $dto->id);
        $page->loadMissing('store');

        $this->repository->delete($page);
        $this->runtimeCacheService->invalidateTenantArtifacts($page->store);
    }
}
