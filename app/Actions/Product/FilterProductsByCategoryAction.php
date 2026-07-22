<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Repositories\Category\CategoryRepository;
use App\Repositories\Product\ProductRepository;
use App\DTOs\Product\FilterProductsByCategoryDTO;
use App\DTOs\Product\CategoryFilterResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FilterProductsByCategoryAction
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
    ) {}

    public function execute(FilterProductsByCategoryDTO $dto): CategoryFilterResult
    {
        $locale = app()->getLocale();

        // Find the main category
        $category = $this->categoryRepository->findBySlugOrFail($dto->slug, $dto->storeId);
        $category->loadMissing(['translations', 'children.translations']);

        // ✅ FIX: Get only immediate children for filters, not all descendants
        $descendantCategories = $category->children->map(function ($child) use ($locale) {
            $translation = $child->translation($locale);
            
            return [
                'id'   => $child->id,
                'name' => $translation?->name ?? $child->slug,
                'slug' => $translation?->slug ?? $child->slug,
            ];
        })->toArray();

        // Build base query
        $query = $this->productRepository->buildBaseQuery($dto->storeId)
            ->addSelect('images.alt_text as alt_text');

        // Apply category filter (subcategory or main category)
        if ($dto->categorySlug !== null) {
            $subCategory = $this->categoryRepository->findBySlug($dto->categorySlug, $dto->storeId);
            if ($subCategory) {
                $subIds = $subCategory->allDescendantIds();
                $query = $this->productRepository->filterByCategory($query, $subIds);
            }
        } else {
            $allIds = $category->allDescendantIds();
            $query = $this->productRepository->filterByCategory($query, $allIds);
        }

        // Get filter ranges
        $filterRanges = $this->productRepository->getFilterRanges($query);

        // Apply additional filters
        $query = $this->productRepository->applyPriceFilter($query, $dto->minPrice, $dto->maxPrice);
        $query = $this->productRepository->applyManufactureFilter($query, $dto->earliestManufacture);
        $query = $this->productRepository->applyExpiryFilter($query, $dto->latestExpiry);

        // Paginate
        $paginator = $this->productRepository->paginate($query, $dto->perPage);

        // Get category translation
        $categoryTranslation = $category->translation($locale);

        return new CategoryFilterResult(
            paginator: $paginator,
            categoryId: (string) $category->id,
            categoryName: $categoryTranslation?->name ?? $category->slug,
            categorySlug: $categoryTranslation?->slug ?? $category->slug,
            breadcrumb: $this->categoryRepository->getBreadcrumb($category),
            descendants: $descendantCategories,
            minPrice: $filterRanges->min_price ?? null,
            maxPrice: $filterRanges->max_price ?? null,
            earliestManufacture: $filterRanges->earliest_manufacture ?? null,
            latestExpiry: $filterRanges->latest_expiry ?? null,
        );
    }
}
