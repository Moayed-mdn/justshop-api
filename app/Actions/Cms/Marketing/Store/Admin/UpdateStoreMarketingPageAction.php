<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Store\Admin;

use App\DTOs\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Repositories\Cms\Marketing\Store\StoreMarketingPageRepository;
use App\Services\Storefront\Runtime\RuntimeCacheService;
use Illuminate\Support\Facades\DB;

class UpdateStoreMarketingPageAction
{
    public function __construct(
        private readonly StoreMarketingPageRepository $repository,
        private readonly RuntimeCacheService $runtimeCacheService,
    ) {}

    public function execute(UpdateStoreMarketingPageDTO $dto): StoreMarketingPage
    {
        $page        = $this->repository->findByIdOrFail($dto->storeId, $dto->id);
        $publishedAt = $this->resolvePublishedAt($dto->status, $dto->publishedAt, $page);

        $page = DB::transaction(function () use ($page, $dto, $publishedAt): StoreMarketingPage {
            $updated = $this->repository->update($page, [
                'title'        => $dto->title,
                'slug'         => $dto->slug,
                'excerpt'      => $dto->excerpt,
                'content'      => $dto->content,
                'status'       => $dto->status->value,
                'published_at' => $publishedAt,
                'seo'          => $dto->seo,
                'template'     => $dto->template?->value,
                'sort_order'   => $dto->sortOrder,
                'updated_by'   => $dto->updatedBy,
            ]);

            // Sync sections only when the key was present in the request
            if ($dto->sections !== null) {
                $this->repository->syncSections($updated, $dto->storeId, $dto->sections);
            }

            return $updated;
        });

        $page->loadMissing('store');
        $this->runtimeCacheService->invalidateTenantArtifacts($page->store);

        return $this->repository->findByIdOrFail($dto->storeId, $page->id);
    }

    private function resolvePublishedAt(
        MarketingPageStatusEnum $status,
        ?string $publishedAt,
        StoreMarketingPage $existing,
    ): ?string {
        return match ($status) {
            MarketingPageStatusEnum::DRAFT     => null,
            MarketingPageStatusEnum::PUBLISHED => $publishedAt
                ?: ($existing->published_at?->toDateTimeString() ?? now()->toDateTimeString()),
            MarketingPageStatusEnum::SCHEDULED => $publishedAt,
        };
    }
}
