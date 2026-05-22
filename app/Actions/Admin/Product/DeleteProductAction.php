<?php

namespace App\Actions\Admin\Product;

use App\DTOs\Admin\Product\DeleteProductDTO;
use App\Enums\RoleEnum;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Repositories\Admin\Product\AdminProductRepository;
use Illuminate\Support\Facades\Auth;

class DeleteProductAction
{
    public function __construct(
        private AdminProductRepository $repository,
    ) {}

    public function execute(DeleteProductDTO $dto): void
    {
        $product = $this->repository->findInStore($dto->productId, $dto->storeId);
        $this->repository->softDelete($product);
    }
}
