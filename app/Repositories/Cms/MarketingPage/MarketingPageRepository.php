<?php

declare(strict_types=1);

namespace App\Repositories\Cms\MarketingPage;

use App\DTOs\Cms\MarketingPage\Admin\ListMarketingPagesDTO;
use App\Exceptions\NotFoundException;
use App\Models\Cms\MarketingPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class MarketingPageRepository
{
    public function paginateAdmin(ListMarketingPagesDTO $dto): LengthAwarePaginator
    {
        $query = MarketingPage::query()
            ->with([
                'creator:id,name',
                'updater:id,name',
            ])
            ->orderBy('type');

        if ($dto->type !== null) {
            $query->where('type', $dto->type->value);
        }

        if ($dto->status !== null && $dto->status !== 'all') {
            $query->where('status', $dto->status);
        }

        if ($dto->search !== null) {
            $like = '%' . strtolower($dto->search) . '%';

            $query->where(function (Builder $builder) use ($like): void {
                $builder->whereRaw('LOWER(type) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(title AS CHAR)) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(slug AS CHAR)) LIKE ?', [$like]);
            });
        }

        return $query->paginate($dto->perPage);
    }

    public function findByIdOrFail(int $id): MarketingPage
    {
        $page = MarketingPage::query()
            ->with([
                'creator:id,name',
                'updater:id,name',
            ])
            ->find($id);

        if ($page === null) {
            throw new NotFoundException(__('cms.page_not_found'));
        }

        return $page;
    }

    public function findPublishedBySlugOrFail(
        string $locale,
        string $fallbackLocale,
        string $slug,
    ): MarketingPage {
        $page = MarketingPage::query()
            ->published()
            ->where(function (Builder $builder) use ($locale, $fallbackLocale, $slug): void {
                $builder->where("slug->{$locale}", $slug)
                    ->orWhere("slug->{$fallbackLocale}", $slug);
            })
            ->first();

        if ($page === null) {
            throw new NotFoundException(__('cms.page_not_found'));
        }

        return $page;
    }

    public function create(array $attributes): MarketingPage
    {
        return MarketingPage::query()->create($attributes);
    }

    public function update(MarketingPage $page, array $attributes): MarketingPage
    {
        $page->update($attributes);

        return $page->fresh(['creator:id,name', 'updater:id,name']) ?? $page;
    }

    public function delete(MarketingPage $page): void
    {
        $page->delete();
    }

    public function existsForType(string $type, ?int $ignoreId = null): bool
    {
        return MarketingPage::query()
            ->when($ignoreId !== null, fn (Builder $builder) => $builder->where('id', '!=', $ignoreId))
            ->where('type', $type)
            ->exists();
    }

    public function slugExists(string $locale, string $slug, ?int $ignoreId = null): bool
    {
        return MarketingPage::query()
            ->when($ignoreId !== null, fn (Builder $builder) => $builder->where('id', '!=', $ignoreId))
            ->where("slug->{$locale}", $slug)
            ->exists();
    }
}
