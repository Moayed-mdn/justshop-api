<?php

namespace App\Actions\Admin\Product;

use App\DTOs\Admin\Product\RestoreProductDTO;
use App\Enums\ErrorCode;
use App\Enums\RoleEnum;
use App\Exceptions\BaseApiException;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Product;
use App\Repositories\Admin\Product\AdminProductRepository;
use Illuminate\Support\Facades\Auth;

class RestoreProductAction
{
    public function __construct(
        private AdminProductRepository $repository,
    ) {}

    public function execute(RestoreProductDTO $dto): Product
    {
        $product = $this->repository->findTrashedInStore($dto->productId, $dto->storeId);

        if (!$product) {
            throw new BaseApiException(__('admin.product_not_found'), 404, ErrorCode::PRD_002->value);
        }

        return $this->repository->refreshEditorProduct(
            $this->repository->restore($product)
        );
    }
}
