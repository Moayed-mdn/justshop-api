<?php

declare(strict_types=1);

namespace App\Repositories\Cms\Marketing\Store;

use App\Exceptions\NotFoundException;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Cms\Marketing\Store\StoreMarketingSection;
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

    /**
     * Sync sections for a page.
     * Replaces all existing sections with the provided array.
     */
    public function syncSections(StoreMarketingPage $page, int $storeId, array $sections): void
    {
        $page->sections()->delete();

        $allowed = ['section_type', 'identifier', 'sort_order', 'title', 'subtitle', 'content', 'settings', 'is_active'];

        $now     = now()->toDateTimeString();
        $records = [];

        foreach ($sections as $index => $section) {
            // Normalise: frontend may send 'type' instead of 'section_type'
            if (!isset($section['section_type']) && isset($section['type'])) {
                $section['section_type'] = $section['type'];
            }
            unset($section['type']);

            // Cast JSON fields to strings for raw insert
            foreach (['title', 'subtitle', 'content', 'settings'] as $jsonField) {
                if (isset($section[$jsonField]) && is_array($section[$jsonField])) {
                    $section[$jsonField] = json_encode($section[$jsonField]);
                }
            }

            $records[] = array_merge(
                array_intersect_key($section, array_flip($allowed)),
                [
                    'store_id'                => $storeId,
                    'store_marketing_page_id' => $page->id,
                    'sort_order'              => $section['sort_order'] ?? $index,
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ]
            );
        }

        if (!empty($records)) {
            StoreMarketingSection::insert($records);
        }
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
