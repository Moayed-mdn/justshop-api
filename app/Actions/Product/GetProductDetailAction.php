<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\DTOs\Product\GetProductDetailDTO;
use App\Models\Product;
use App\Services\ProductService;

class GetProductDetailAction
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function execute(GetProductDetailDTO $dto): Product
    {
        $product = $this->productService->findProductBySlugOrFail($dto->slug, $dto->storeId);

        $product->load([
            'translations',
            'category.translations',
            'brand',
            'activeVariants.optionValues.option',
            'activeVariants.images',
        ]);

        return $product;
    }
}