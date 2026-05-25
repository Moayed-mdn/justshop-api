<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Platform\Admin;

use App\DTOs\Cms\Marketing\Platform\Admin\PublishPlatformMarketingPageDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Repositories\Cms\Marketing\Platform\PlatformMarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;
use App\Services\Cms\Seo\IsrRevalidationService;
use Illuminate\Support\Facades\DB;

class PublishPlatformMarketingPageAction
{
    public function __construct(
        private readonly PlatformMarketingPageRepository $repository,
        private readonly MarketingPageCacheService $cacheService,
        private readonly IsrRevalidationService $isrService,
    ) {}

    public function execute(PublishPlatformMarketingPageDTO $dto): PlatformMarketingPage
    {
        $page = $this->repository->findByIdOrFail($dto->id);

        $page = DB::transaction(function () use ($page, $dto): PlatformMarketingPage {
            return $this->repository->update($page, [
                'status'       => MarketingPageStatusEnum::PUBLISHED->value,
                'published_at' => $dto->publishedAt ?: now()->toDateTimeString(),
                'updated_by'   => $dto->updatedBy,
            ]);
        });

        // Invalidate page content cache + sitemap cache
        $this->cacheService->invalidateAll();

        // Trigger Next.js ISR revalidation
        $slugMap = is_array($page->slug) ? $page->slug : [];
        $paths   = $this->isrService->pathsFromSlugMap($slugMap);
        $this->isrService->revalidatePaths($paths);

        return $page;
    }
}
