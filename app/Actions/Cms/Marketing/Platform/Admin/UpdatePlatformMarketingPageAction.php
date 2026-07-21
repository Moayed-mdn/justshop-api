<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Platform\Admin;

use App\DTOs\Cms\Marketing\Platform\Admin\UpdatePlatformMarketingPageDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Repositories\Cms\Marketing\Platform\PlatformMarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePlatformMarketingPageAction
{
    public function __construct(
        private PlatformMarketingPageRepository $repository,
        private MarketingPageCacheService $cacheService,
    ) {}

    public function execute(UpdatePlatformMarketingPageDTO $dto): PlatformMarketingPage
    {
        $page = $this->repository->findByIdOrFail($dto->id);
        $previousSlugs = is_array($page->slug) ? array_values($page->slug) : [];

        $this->assertSlugsAreUnique($dto->slug, $page->id);
        $publishedAt = $this->resolvePublishedAt($dto->status, $dto->publishedAt);

        $page = DB::transaction(function () use ($page, $dto, $publishedAt): PlatformMarketingPage {
            return $this->repository->update($page, [
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
                'updated_by' => $dto->updatedBy,
            ]);
        });

        // Targeted invalidation: flush previous slugs + new slugs so stale
        // cache entries are evicted regardless of which locale changed.
        $this->cacheService->invalidateForSlugMap($page->slug ?? [], $previousSlugs);

        return $page;
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
