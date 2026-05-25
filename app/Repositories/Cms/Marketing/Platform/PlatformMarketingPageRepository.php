<?php

declare(strict_types=1);

namespace App\Repositories\Cms\Marketing\Platform;

use App\DTOs\Cms\Marketing\Platform\Admin\ListPlatformMarketingPagesDTO;
use App\Exceptions\NotFoundException;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class PlatformMarketingPageRepository
{
    public function paginateAdmin(ListPlatformMarketingPagesDTO $dto): LengthAwarePaginator
    {
        $query = PlatformMarketingPage::query()
            ->with([
                'creator:id,name',
                'updater:id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($dto->status !== null && $dto->status !== 'all') {
            $query->where('status', $dto->status);
        }

        if ($dto->search !== null) {
            $like = '%' . strtolower($dto->search) . '%';

            $query->where(function (Builder $builder) use ($like): void {
                $builder->whereRaw('LOWER(CAST(title AS CHAR)) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(slug AS CHAR)) LIKE ?', [$like]);
            });
        }

        return $query->paginate($dto->perPage);
    }

    public function findByIdOrFail(int $id): PlatformMarketingPage
    {
        $page = PlatformMarketingPage::query()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'sections',
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
    ): PlatformMarketingPage {
        $page = PlatformMarketingPage::query()
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

    public function create(array $attributes): PlatformMarketingPage
    {
        return PlatformMarketingPage::query()->create($attributes);
    }

    public function update(PlatformMarketingPage $page, array $attributes): PlatformMarketingPage
    {
        $page->update($attributes);

        return $page->fresh(['creator:id,name', 'updater:id,name', 'sections']) ?? $page;
    }

    public function delete(PlatformMarketingPage $page): void
    {
        $page->delete();
    }

    public function slugExists(string $locale, string $slug, ?int $ignoreId = null): bool
    {
        return PlatformMarketingPage::query()
            ->when($ignoreId !== null, fn (Builder $builder) => $builder->where('id', '!=', $ignoreId))
            ->where("slug->{$locale}", $slug)
            ->exists();
    }
}
