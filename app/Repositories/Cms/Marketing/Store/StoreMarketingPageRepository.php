<?php

declare(strict_types=1);

namespace App\Repositories\Cms\Marketing\Store;

use App\Exceptions\NotFoundException;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class StoreMarketingPageRepository
{
    public function paginateAdmin(int $storeId, int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = StoreMarketingPage::query()
            ->where('store_id', $storeId)
            ->with([
                'creator:id,name',
                'updater:id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($status !== null && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== null) {
            $like = '%' . strtolower($search) . '%';

            $query->where(function (Builder $builder) use ($like): void {
                $builder->whereRaw('LOWER(CAST(title AS CHAR)) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(slug AS CHAR)) LIKE ?', [$like]);
            });
        }

        return $query->paginate($perPage);
    }

    public function findByIdOrFail(int $storeId, int $id): StoreMarketingPage
    {
        $page = StoreMarketingPage::query()
            ->where('store_id', $storeId)
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
        int $storeId,
        string $locale,
        string $fallbackLocale,
        string $slug,
    ): StoreMarketingPage {
        $page = StoreMarketingPage::query()
            ->where('store_id', $storeId)
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

    public function create(array $attributes): StoreMarketingPage
    {
        return StoreMarketingPage::query()->create($attributes);
    }

    public function update(StoreMarketingPage $page, array $attributes): StoreMarketingPage
    {
        $page->update($attributes);

        return $page->fresh(['creator:id,name', 'updater:id,name', 'sections']) ?? $page;
    }

    public function delete(StoreMarketingPage $page): void
    {
        $page->delete();
    }

    public function slugExists(int $storeId, string $locale, string $slug, ?int $ignoreId = null): bool
    {
        return StoreMarketingPage::query()
            ->where('store_id', $storeId)
            ->when($ignoreId !== null, fn (Builder $builder) => $builder->where('id', '!=', $ignoreId))
            ->where("slug->{$locale}", $slug)
            ->exists();
    }
}
