<?php

declare(strict_types=1);

namespace App\Actions\Cms\MarketingPage\Admin;

use App\DTOs\Cms\MarketingPage\Admin\UpdateMarketingPageDTO;
use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Models\Cms\MarketingPage;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateMarketingPageAction
{
    public function __construct(
        private MarketingPageRepository $repository,
        private MarketingPageCacheService $cacheService,
    ) {}

    public function execute(UpdateMarketingPageDTO $dto): MarketingPage
    {
        $page = $this->repository->findByIdOrFail($dto->id);
        $previousSlugs = is_array($page->slug) ? array_values($page->slug) : [];

        $this->assertTypeMatches($page, $dto);
        $this->assertSlugsAreUnique($dto->slug, $page->id);
        $publishedAt = $this->resolvePublishedAt($dto->status, $dto->publishedAt);

        $page = DB::transaction(function () use ($page, $dto, $publishedAt): MarketingPage {
            return $this->repository->update($page, [
                'slug' => $dto->slug,
                'title' => $dto->title,
                'sections' => $dto->sections,
                'seo' => $dto->seo,
                'status' => $dto->status->value,
                'published_at' => $publishedAt,
                'updated_by' => $dto->updatedBy,
            ]);
        });

        $this->cacheService->invalidateForPage($page, $previousSlugs);

        return $page;
    }

    private function assertTypeMatches(
        MarketingPage $page,
        UpdateMarketingPageDTO $dto,
    ): void {
        if ($page->type->value !== $dto->type->value) {
            throw ValidationException::withMessages([
                'type' => __('cms.type_is_immutable'),
            ]);
        }
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
