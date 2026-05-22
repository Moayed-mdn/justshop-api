<?php

declare(strict_types=1);

namespace App\Actions\Cms\MarketingPage\Admin;

use App\DTOs\Cms\MarketingPage\Admin\PublishMarketingPageDTO;
use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Models\Cms\MarketingPage;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;
use App\Services\Cms\Seo\IsrRevalidationService;
use Illuminate\Support\Facades\DB;

class PublishMarketingPageAction
{
    public function __construct(
        private readonly MarketingPageRepository $repository,
        private readonly MarketingPageCacheService $cacheService,
        private readonly IsrRevalidationService $isrService,
    ) {}

    public function execute(PublishMarketingPageDTO $dto): MarketingPage
    {
        $page = $this->repository->findByIdOrFail($dto->id);

        $page = DB::transaction(function () use ($page, $dto): MarketingPage {
            return $this->repository->update($page, [
                'status'       => MarketingPageStatusEnum::PUBLISHED->value,
                'published_at' => $dto->publishedAt ?: now()->toDateTimeString(),
                'updated_by'   => $dto->updatedBy,
            ]);
        });

        // Invalidate page content cache + sitemap cache
        $this->cacheService->invalidateForPage($page);

        // Trigger Next.js ISR revalidation (non-blocking, never throws)
        $slugMap = is_array($page->slug) ? $page->slug : [];
        $paths   = $this->isrService->pathsFromSlugMap($slugMap);
        $this->isrService->revalidatePaths($paths);

        return $page;
    }
}
