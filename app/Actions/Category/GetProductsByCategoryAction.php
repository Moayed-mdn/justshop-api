<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\DTOs\Category\GetProductsByCategoryDTO;
use App\Services\ProductService;
use Illuminate\Pagination\LengthAwarePaginator;

class GetProductsByCategoryAction
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function execute(GetProductsByCategoryDTO $dto): array
    {
        $locale = app()->getLocale();

        $category = $this->productService->findCategoryBySlugOrFail($dto->slug, $dto->storeId);
        $category->loadMissing(['translations', 'children.translations', 'children.children.translations']);

        $descendantCategories = $this->productService->flattenCategoryDescendants($category, $locale);

        $query = $this->productService->buildBaseProductQuery($dto->storeId)
            ->addSelect('images.alt_text as alt_text');

        if ($dto->categorySlug) {
            $subCategory = $this->productService->findCategoryBySlug($dto->categorySlug, $dto->storeId);

            if ($subCategory) {
                $subIds = $subCategory->allDescendantIds();
                $query->whereIn('products.category_id', $subIds);
            }
        } else {
            $allIds = $category->allDescendantIds();
            $query->whereIn('products.category_id', $allIds);
        }

        $variantStatus = $this->productService->getProductFilterRanges($query);

        if ($dto->minPrice !== null) {
            $query->whereHas('variants', function ($q) use ($dto) {
                $q->where('price', '>=', $dto->minPrice);
            });
        }

        if ($dto->maxPrice !== null) {
            $query->whereHas('variants', function ($q) use ($dto) {
                $q->where('price', '<=', $dto->maxPrice);
            });
        }

        if ($dto->earliestManufacture) {
            $query->whereHas('variants', function ($q) use ($dto) {
                $q->where('manufacture_date', '>=', $dto->earliestManufacture);
            });
        }

        if ($dto->latestExpiry) {
            $query->whereHas('variants', function ($q) use ($dto) {
                $q->where('expiry_date', '>=', $dto->latestExpiry);
            });
        }

        if ($dto->minRating !== null) {
            $query->whereIn('products.id', function ($sub) use ($dto) {
                $sub->select('product_id')
                     ->from('reviews')
                     ->where('is_approved', true)
                     ->groupBy('product_id')
                     ->havingRaw('AVG(rating) >= ?', [$dto->minRating]);
            });
        }

        $paginator = $query->paginate($dto->perPage);
        $categoryTranslation = $category->translation($locale);

        return [
            'paginator' => $paginator,
            'category' => [
                'id'         => $category->id,
                'name'       => $categoryTranslation?->name ?? $category->slug,
                'slug'       => $categoryTranslation?->slug ?? $category->slug,
                'breadcrumb' => $category->breadcrumb,
            ],
            'descendant_categories' => $descendantCategories,
            'variant_status' => $variantStatus,
        ];
    }
}
