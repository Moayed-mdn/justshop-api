<?php

declare(strict_types=1);

namespace App\Repositories\Category;

use App\Models\Category;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository
{
    protected function modelClass(): string
    {
        return Category::class;
    }

    // ── Queries ────────────────────────────────────────────────

    public function search(string $term, int $limit): Collection
    {
        $storeId = $this->getCurrentStoreId();

        $categories = $this->scopedQuery()
            ->whereHas('translations', function (Builder $q) use ($term) {
                $q->where('category_translations.name', 'LIKE', "%{$term}%");
            })
            ->with('translations')
            ->limit($limit)
            ->get();

        // Add products_count including descendants using recursive CTE
        foreach ($categories as $category) {
            $category->products_count = $this->countProductsWithDescendants($category->id, $storeId);
        }

        return $categories;
    }

    /**
     * Count all active products in a category and its descendants using recursive CTE
     */
    private function countProductsWithDescendants(int $categoryId, int $storeId): int
    {
        // Use recursive CTE to get all descendant category IDs
        $query = "
            WITH RECURSIVE category_tree AS (
                -- Base case: the category itself
                SELECT id FROM categories WHERE id = ? AND store_id = ?
                
                UNION ALL
                
                -- Recursive case: all descendants
                SELECT c.id 
                FROM categories c
                INNER JOIN category_tree ct ON c.parent_id = ct.id
                WHERE c.store_id = ?
            )
            SELECT COUNT(*) as count
            FROM products p
            WHERE p.category_id IN (SELECT id FROM category_tree)
              AND p.store_id = ?
              AND p.is_active = 1
              AND p.deleted_at IS NULL
        ";

        $result = \DB::select($query, [$categoryId, $storeId, $storeId, $storeId]);
        
        return (int) $result[0]->count;
    }

    /**
     * Get all descendant IDs recursively (fallback method, not used)
     */
    private function getAllDescendantIds(Category $category): array
    {
        $ids = [];
        
        if ($category->relationLoaded('children')) {
            foreach ($category->children as $child) {
                $ids[] = $child->id;
                $child->loadMissing('children');
                $ids = array_merge($ids, $this->getAllDescendantIds($child));
            }
        }

        return $ids;
    }

    public function getRootCategories(
        int $storeId,
        ?string $type = null,
    ): Collection {
        $query = $this->scopedQuery()
            ->whereNull('parent_id')
            ->with(['translations', 'children.translations'])
            ->orderBy('sort_order');

        if ($type !== null) {
            $query->where('type', $type);
        }

        $categories = $query->get();
        
        // Calculate product count including descendants using recursive CTE
        foreach ($categories as $category) {
            $category->products_count = $this->countProductsWithDescendants($category->id, $storeId);
        }
        
        return $categories;
    }

    public function getChildCategories(
        int $parentId,
        int $storeId,
    ): Collection {
        return $this->scopedQuery()
            ->where('parent_id', $parentId)
            ->with(['translations', 'children.translations'])
            ->withCount(['products' => fn(Builder $q) => $q
                ->where('is_active', true)
                ->where('store_id', $storeId),
            ])
            ->orderBy('sort_order')
            ->get();
    }

    public function paginate(
        int $storeId,
        ?int $parentId,
        ?bool $isActive,
        int $perPage,
    ): LengthAwarePaginator {
        $query = Category::query()
            ->where('store_id', $storeId)
            ->with(['translations', 'parent.translations'])
            ->withCount(['products' => fn(Builder $q) => $q
                ->where('store_id', $storeId),
            ])
            ->orderBy('sort_order');

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->paginate($perPage);
    }

    public function findById(
        int $id,
        int $storeId,
    ): ?Category {
        return Category::query()
            ->where('store_id', $storeId)
            ->where('id', $id)
            ->with([
                'translations',
                'parent.translations',
                'children.translations',
            ])
            ->withCount(['products' => fn(Builder $q) => $q
                ->where('store_id', $storeId),
            ])
            ->first();
    }

    public function findByIdOrFail(
        int $id,
        int $storeId,
    ): Category {
        $category = $this->findById($id, $storeId);

        if ($category === null) {
            throw new \App\Exceptions\Category\CategoryNotFoundException();
        }

        return $category;
    }

    public function findTrashedById(
        int $id,
        int $storeId,
    ): ?Category {
        return Category::withTrashed()
            ->where('store_id', $storeId)
            ->where('id', $id)
            ->with('translations')
            ->first();
    }

    public function findBySlug(
        string $slug,
        int $storeId,
    ): ?Category {
        return $this->scopedQuery()
            ->where('slug', $slug)
            ->with(['translations', 'children.translations'])
            ->withCount(['products' => fn(Builder $q) => $q
                ->where('is_active', true)
                ->where('store_id', $storeId),
            ])
            ->first();
    }

    public function findBySlugOrFail(
        string $slug,
        int $storeId,
    ): Category {
        return Category::findByLocalizedSlugOrFail($slug, $storeId);
    }

    public function slugExistsForStore(
        string $slug,
        int $storeId,
        ?int $excludeId = null,
    ): bool {
        $query = Category::query()
            ->where('store_id', $storeId)
            ->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function searchForAutocomplete(string $query, string $locale, int $limit): Collection
    {
        $storeId = $this->getCurrentStoreId();
        $term = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $query);

        $categoryIds = \DB::table('category_translations')
            ->join('categories', 'categories.id', '=', 'category_translations.category_id')
            ->where('category_translations.locale', $locale)
            ->where('category_translations.name', 'LIKE', "%{$term}%")
            ->where('categories.store_id', $storeId)
            ->orderByRaw("
                CASE
                    WHEN category_translations.name = ? THEN 1
                    WHEN category_translations.name LIKE ? THEN 2
                    ELSE 3
                END
            ", [$query, "{$query}%"])
            ->limit($limit)
            ->pluck('categories.id');

        if ($categoryIds->isEmpty()) {
            return new Collection();
        }

        return $this->scopedQuery()
            ->whereIn('id', $categoryIds)
            ->with('translations')
            ->get();
    }

    public function hasActiveChildren(int $id, int $storeId): bool
    {
        return Category::query()
            ->where('store_id', $storeId)
            ->where('parent_id', $id)
            ->exists();
    }

    public function hasProducts(int $id, int $storeId): bool
    {
        return Category::query()
            ->where('store_id', $storeId)
            ->where('id', $id)
            ->whereHas('products', fn(Builder $q) => $q
                ->where('store_id', $storeId)
            )
            ->exists();
    }

    // ── Mutations ──────────────────────────────────────────────

    public function create(
        int $storeId,
        string $slug,
        ?int $parentId,
        int $sortOrder,
        bool $isActive,
        array $translations,
    ): Category {
        $category = Category::create([
            'store_id'   => $storeId,
            'slug'       => $slug,
            'parent_id'  => $parentId,
            'sort_order' => $sortOrder,
            'is_active'  => $isActive,
        ]);

        foreach ($translations as $translation) {
            $category->translations()->create($translation);
        }

        $category->load(['translations', 'parent.translations']);

        return $category;
    }

    public function update(
        Category $category,
        string $slug,
        ?int $parentId,
        int $sortOrder,
        bool $isActive,
        array $translations,
    ): Category {
        $category->update([
            'slug'       => $slug,
            'parent_id'  => $parentId,
            'sort_order' => $sortOrder,
            'is_active'  => $isActive,
        ]);

        foreach ($translations as $translation) {
            $category->translations()->updateOrCreate(
                ['locale' => $translation['locale']],
                [
                    'name' => $translation['name'],
                    'slug' => $translation['slug'],
                ],
            );
        }

        $category->load(['translations', 'parent.translations']);

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function restore(Category $category): void
    {
        $category->restore();
    }

    // ── Flatten helpers ────────────────────────────────────────

    public function flattenDescendantsWithTranslations(
        Category $category,
        string $locale,
    ): array {
        $result = collect();
        $this->flattenDescendantsRecursive($category, $result, $locale);

        return $result->toArray();
    }

    private function flattenDescendantsRecursive(
        Category $category,
        mixed &$result,
        string $locale,
    ): void {
        foreach ($category->children as $child) {
            $translation = $child->translation($locale);

            $result->push([
                'id'   => $child->id,
                'name' => $translation?->name ?? $child->slug,
                'slug' => $translation?->slug ?? $child->slug,
            ]);

            $this->flattenDescendantsRecursive($child, $result, $locale);
        }
    }

    /**
     * Get root categories formatted for filter display.
     * Returns array with id, name (localized), and slug for each category.
     *
     * @param int $storeId Store ID for isolation
     * @param string $locale Current locale for translations
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    public function getRootCategoriesForFilters(int $storeId, string $locale): array
    {
        $categories = $this->getRootCategories($storeId);

        return $categories->map(function (Category $category) use ($locale) {
            $translation = $category->translation($locale);

            return [
                'id'   => $category->id,
                'name' => $translation?->name ?? $category->slug,
                'slug' => $translation?->slug ?? $category->slug,
            ];
        })->toArray();
    }

    public function getBreadcrumb(Category $category): array
    {
        $locale    = app()->getLocale();
        $breadcrumb = [];
        $current   = $category;

        while ($current) {
            $translation = $current->translation($locale);

            array_unshift($breadcrumb, [
                'id'   => $current->id,
                'name' => $translation?->name ?? $current->slug,
                'slug' => $translation?->slug ?? $current->slug,
            ]);

            $current = $current->parent;
        }

        return $breadcrumb;
    }
}
