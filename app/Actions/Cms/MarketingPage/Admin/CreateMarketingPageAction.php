<?php

declare(strict_types=1);

namespace App\Actions\Cms\MarketingPage\Admin;

use App\DTOs\Cms\MarketingPage\Admin\CreateMarketingPageDTO;
use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Models\Cms\MarketingPage;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use App\Services\Cms\MarketingPageCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateMarketingPageAction
{
    public function __construct(
        private MarketingPageRepository $repository,
        private MarketingPageCacheService $cacheService,
    ) {}

    public function execute(CreateMarketingPageDTO $dto): MarketingPage
    {
        $this->assertTypeIsAvailable($dto->type->value);
        $this->assertSlugsAreUnique($dto->slug);
        $publishedAt = $this->resolvePublishedAt($dto->status, $dto->publishedAt);

        $page = DB::transaction(function () use ($dto, $publishedAt): MarketingPage {
            return $this->repository->create([
                'type' => $dto->type->value,
                'slug' => $dto->slug,
                'title' => $dto->title,
                'sections' => $dto->sections,
                'seo' => $dto->seo,
                'status' => $dto->status->value,
                'published_at' => $publishedAt,
                'created_by' => $dto->createdBy,
                'updated_by' => $dto->updatedBy,
            ]);
        });

        $this->cacheService->invalidateForPage($page);

        return $this->repository->findByIdOrFail($page->id);
    }

    private function assertTypeIsAvailable(string $type): void
    {
        if ($this->repository->existsForType($type)) {
            throw ValidationException::withMessages([
                'type' => __('cms.type_already_exists'),
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
