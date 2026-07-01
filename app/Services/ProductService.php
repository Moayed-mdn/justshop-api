<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Repositories\Brand\BrandRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Category\CategoryRepository;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private BrandRepository $brandRepository,
    ) {}

    /**
     * Build the base product query with joins for listing.
     */
    public function buildBaseProductQuery(int $storeId)
    {
        return $this->productRepository->buildBaseQuery($storeId);
    }

    /**
     * Get filter ranges (min/max price, manufacture/expiry dates) for a set of products.
     */
    public function getProductFilterRanges($query): object
    {
        return $this->productRepository->getFilterRanges($query);
    }

    /**
     * Apply price and date filters to a query based on request parameters.
     */
    public function applyFilters($query, Request $request)
    {
        if ($request->filled('min_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '>=', $request->min_price);
            });
        }

        if ($request->filled('max_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '<=', $request->max_price);
            });
        }

        if ($request->filled('earliest_manufacture')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('manufacture_date', '>=', $request->earliest_manufacture);
            });
        }

        if ($request->filled('latest_expiry')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('expiry_date', '>=', $request->latest_expiry);
            });
        }

        return $query;
    }

    /**
     * Get active brands for filter display.
     */
    public function getBrandsForFilters(int $storeId): array
    {
        return $this->brandRepository->getActiveBrandsForStore($storeId);
    }

    /**
     * Get category descendants for filters.
     */
    public function getCategoryDescendants(int $storeId): array
    {
        $locale = app()->getLocale();
        return $this->categoryRepository->getRootCategoriesForFilters($storeId, $locale);
    }

    /**
     * Find a category by its localized slug.
     */
    public function findCategoryBySlug(string $slug, int $storeId): ?Category
    {
        return $this->categoryRepository->findBySlug($slug, $storeId);
    }

    /**
     * Find a category by its localized slug or fail.
     */
    public function findCategoryBySlugOrFail(string $slug, int $storeId): Category
    {
        return $this->categoryRepository->findBySlugOrFail($slug, $storeId);
    }

    /**
     * Flatten category descendants with translations.
     */
    public function flattenCategoryDescendants(Category $category, string $locale): array
    {
        return $this->categoryRepository->flattenDescendantsWithTranslations($category, $locale);
    }

    /**
     * Recursively flatten category descendants.
     */
    private function flattenDescendantsRecursive(Category $category, &$result, string $locale): void
    {
        foreach ($category->children as $child) {
            $translation = $child->translation($locale);

            $result->push([
                'id'   => $child->id,
                'name' => $translation?->name ?? $child->slug,
                'slug' => $translation?->slug ?? $child->slug,
            ]);

            if ($child->relationLoaded('descendants') || $child->relationLoaded('children')) {
                $this->flattenDescendantsRecursive($child, $result, $locale);
            }
        }
    }

    /**
     * Find a product by its localized slug.
     */
    public function findProductBySlug(string $slug, int $storeId): ?Product
    {
        return $this->productRepository->findBySlug($slug, $storeId);
    }

    /**
     * Find a product by its localized slug or fail.
     */
    public function findProductBySlugOrFail(string $slug, int $storeId): Product
    {
        $product = $this->productRepository->findBySlug($slug, $storeId);
        
        if (!$product) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Product not found");
        }

        return $product;
    }

    /**
     * Get related products for a given product.
     */
    public function getRelatedProducts(Product $currentProduct, int $storeId, int $limit = 8): \Illuminate\Support\Collection
    {
        return $this->productRepository->findRelatedProducts($currentProduct, $storeId, $limit);
    }
}
