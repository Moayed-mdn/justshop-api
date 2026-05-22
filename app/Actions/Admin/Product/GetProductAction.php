<?php

namespace App\Actions\Admin\Product;

use App\DTOs\Admin\Product\GetProductDTO;
use App\Models\Product;
use App\Repositories\Admin\Product\AdminProductRepository;

class GetProductAction
{
    public function __construct(
        private AdminProductRepository $repository,
    ) {}

    public function execute(GetProductDTO $dto): Product
    {
        return $this->repository->findEditorProductInStore($dto->productId, $dto->storeId);
    }
}
