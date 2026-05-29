<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Store\Admin;

use App\DTOs\Cms\Marketing\Store\Admin\CreateStoreMarketingPageDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Repositories\Cms\Marketing\Store\StoreMarketingPageRepository;
use App\Services\Storefront\Runtime\RuntimeCacheService;
use Illuminate\Support\Facades\DB;

class CreateStoreMarketingPageAction
{
    public function __construct(
        private readonly StoreMarketingPageRepository $repository,
        private readonly RuntimeCacheService $runtimeCacheService,
    ) {}

    public function execute(CreateStoreMarketingPageDTO $dto): StoreMarketingPage
    {
        $publishedAt = $this->resolvePublishedAt($dto->status, $dto->publishedAt);

        $page = DB::transaction(function () use ($dto, $publishedAt): StoreMarketingPage {
            $page = $this->repository->create([
                'store_id'    => $dto->storeId,
                'title'       => $dto->title,
                'slug'        => $dto->slug,
                'excerpt'     => $dto->excerpt,
                'content'     => $dto->content,
                'status'      => $dto->status->value,
                'published_at' => $publishedAt,
                'seo'         => $dto->seo,
                'template'    => $dto->template?->value,
                'sort_order'  => $dto->sortOrder,
                'created_by'  => $dto->createdBy,
                'updated_by'  => $dto->updatedBy,
            ]);

            if (!empty($dto->sections)) {
                $this->repository->syncSections($page, $dto->storeId, $dto->sections);
            }

            return $page;
        });

        $page->loadMissing('store');
        $this->runtimeCacheService->invalidateTenantArtifacts($page->store);

        return $this->repository->findByIdOrFail($dto->storeId, $page->id);
    }

    private function resolvePublishedAt(MarketingPageStatusEnum $status, ?string $publishedAt): ?string
    {
        return match ($status) {
            MarketingPageStatusEnum::DRAFT      => null,
            MarketingPageStatusEnum::PUBLISHED  => $publishedAt ?: now()->toDateTimeString(),
            MarketingPageStatusEnum::SCHEDULED  => $publishedAt,
        };
    }
}
