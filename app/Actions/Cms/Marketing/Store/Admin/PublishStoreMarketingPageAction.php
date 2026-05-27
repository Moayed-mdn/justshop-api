<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Store\Admin;

use App\DTOs\Cms\Marketing\Store\Admin\PublishStoreMarketingPageDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Repositories\Cms\Marketing\Store\StoreMarketingPageRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishStoreMarketingPageAction
{
    public function __construct(
        private readonly StoreMarketingPageRepository $repository,
    ) {}

    public function execute(PublishStoreMarketingPageDTO $dto): StoreMarketingPage
    {
        $page = $this->repository->findByIdOrFail($dto->storeId, $dto->id);

        if ($page->status === MarketingPageStatusEnum::PUBLISHED && $page->isPublished()) {
            throw ValidationException::withMessages([
                'status' => __('cms.page_already_published'),
            ]);
        }

        $page = DB::transaction(function () use ($page, $dto): StoreMarketingPage {
            return $this->repository->update($page, [
                'status'       => MarketingPageStatusEnum::PUBLISHED->value,
                'published_at' => $dto->publishedAt ?: now()->toDateTimeString(),
                'updated_by'   => $dto->updatedBy,
            ]);
        });

        // Future hook: ISR revalidation and cache invalidation go here
        // when the store public CMS endpoint is activated.

        return $page;
    }
}
