<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\DTOs\Product\ListProductsDTO;
use App\Services\ProductService;
use Illuminate\Pagination\LengthAwarePaginator;

class ListProductsAction
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function execute(ListProductsDTO $dto): array
    {
        $query = $this->productService->buildBaseProductQuery($dto->storeId);

        $descendants = $this->productService->getCategoryDescendants($dto->storeId);

        if ($dto->categorySlug) {
            $category = $this->productService->findCategoryBySlugOrFail($dto->categorySlug, $dto->storeId);
            $descendantsWithSelf = $category->allDescendantIds();

            $query->whereHas('category', function ($query) use ($descendantsWithSelf) {
                $query->whereIn('id', $descendantsWithSelf);
            });
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

        if (!empty($dto->brandSlugs)) {
            $query->whereHas('brand', function ($q) use ($dto) {
                $q->whereIn('slug', $dto->brandSlugs);
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

        $brands = $this->productService->getBrandsForFilters($dto->storeId);

        $paginator = $query->paginate($dto->perPage);

        return [
            'paginator' => $paginator,
            'descendants' => $descendants,
            'variant_status' => $variantStatus,
            'brands' => $brands,
        ];
    }
}