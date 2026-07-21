<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Platform\Admin;

use App\DTOs\Cms\Marketing\Platform\Admin\CreatePlatformMarketingPageDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Repositories\Cms\Marketing\Platform\PlatformMarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePlatformMarketingPageAction
{
    public function __construct(
        private PlatformMarketingPageRepository $repository,
        private MarketingPageCacheService $cacheService,
    ) {}

    public function execute(CreatePlatformMarketingPageDTO $dto): PlatformMarketingPage
    {
        $this->assertSlugsAreUnique($dto->slug);
        $publishedAt = $this->resolvePublishedAt($dto->status, $dto->publishedAt);

        $page = DB::transaction(function () use ($dto, $publishedAt): PlatformMarketingPage {
            return $this->repository->create([
                'type' => $dto->type?->value,
                'title' => $dto->title,
                'slug' => $dto->slug,
                'excerpt' => $dto->excerpt,
                'content' => $dto->content,
                'status' => $dto->status->value,
                'published_at' => $publishedAt,
                'seo' => $dto->seo,
                'template' => $dto->template?->value,
                'sort_order' => $dto->sortOrder,
                'created_by' => $dto->createdBy,
                'updated_by' => $dto->updatedBy,
            ]);
        });

        $this->cacheService->invalidateAll(); // Invalidate all since it's a new page that might affect sitemaps/lists

        return $this->repository->findByIdOrFail((int) $page->id);
    }

    private function assertSlugsAreUnique(array $slugs, ?int $ignoreId = null): void
    {
        foreach ($slugs as $locale => $slug) {
            if ($this->repository->slugExists((string) $locale, (string) $slug, $ignoreId)) {
                throw ValidationException::withMessages([
                    "slug.{$locale}" => __('cms.slug_already_exists'),
                ]);
            }
        }
    }

    private function resolvePublishedAt(
        MarketingPageStatusEnum $status,
        ?string $publishedAt,
    ): ?string {
        return match ($status) {
            MarketingPageStatusEnum::DRAFT => null,
            MarketingPageStatusEnum::PUBLISHED => $publishedAt ?: now()->toDateTimeString(),
            MarketingPageStatusEnum::SCHEDULED => $publishedAt,
        };
    }
}
