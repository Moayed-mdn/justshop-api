<?php

declare(strict_types=1);

namespace App\Actions\Admin\Brand;

use App\DTOs\Admin\Brand\DeleteBrandDTO;
use App\Exceptions\Brand\BrandHasProductsException;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\User;
use App\Repositories\Brand\BrandRepository;

class DeleteBrandAction
{
    public function __construct(
        private BrandRepository $brandRepository,
    ) {}

    public function execute(
        DeleteBrandDTO $dto,
        User $user,
    ): void {
        if (!$user->hasRole('super_admin')
            && !$user->stores()->where('store_id', $dto->storeId)->exists()
        ) {
            throw new UnauthorizedStoreAccessException();
        }

        $brand = $this->brandRepository->findByIdOrFail(
            id:      $dto->brandId,
            storeId: $dto->storeId,
        );

        if ($this->brandRepository->hasProducts(
            id:      $brand->id,
            storeId: $dto->storeId,
        )) {
            throw new BrandHasProductsException();
        }

        $this->brandRepository->delete($brand);
    }
}
