<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Store\Admin;

use App\DTOs\Cms\Marketing\Store\Admin\PublishStoreMarketingPageDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Repositories\Cms\Marketing\Store\StoreMarketingPageRepository;
use App\Services\Storefront\Runtime\RuntimeCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnpublishStoreMarketingPageAction
{
    public function __construct(
        private readonly StoreMarketingPageRepository $repository,
        private readonly RuntimeCacheService $runtimeCacheService,
    ) {}

    /**
     * Revert a published page back to draft.
     * Reuses PublishStoreMarketingPageDTO — only id, storeId, updatedBy are used.
     */
    public function execute(PublishStoreMarketingPageDTO $dto): StoreMarketingPage
    {
        $page = $this->repository->findByIdOrFail($dto->storeId, $dto->id);

        if ($page->status === MarketingPageStatusEnum::DRAFT) {
            throw ValidationException::withMessages([
                'status' => __('cms.page_already_draft'),
            ]);
        }

        $page = DB::transaction(function () use ($page, $dto): StoreMarketingPage {
            return $this->repository->update($page, [
                'status'       => MarketingPageStatusEnum::DRAFT->value,
                'published_at' => null,
                'updated_by'   => $dto->updatedBy,
            ]);
        });

        $page->loadMissing('store');
        $this->runtimeCacheService->invalidateTenantArtifacts($page->store);

        return $page;
    }
}
