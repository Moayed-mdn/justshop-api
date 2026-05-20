<?php

declare(strict_types=1);

namespace App\Actions\Cms\MarketingPage\Admin;

use App\DTOs\Cms\MarketingPage\Admin\PublishMarketingPageDTO;
use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Models\Cms\MarketingPage;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;
use Illuminate\Support\Facades\DB;

class PublishMarketingPageAction
{
    public function __construct(
        private MarketingPageRepository $repository,
        private MarketingPageCacheService $cacheService,
    ) {}

    public function execute(PublishMarketingPageDTO $dto): MarketingPage
    {
        $page = $this->repository->findByIdOrFail($dto->id);

        $page = DB::transaction(function () use ($page, $dto): MarketingPage {
            return $this->repository->update($page, [
                'status' => MarketingPageStatusEnum::PUBLISHED->value,
                'published_at' => $dto->publishedAt ?: now()->toDateTimeString(),
                'updated_by' => $dto->updatedBy,
            ]);
        });

        $this->cacheService->invalidateForPage($page);

        return $page;
    }
}
