<?php

declare(strict_types=1);

namespace App\Actions\Admin\Brand;

use App\DTOs\Admin\Brand\UpdateBrandDTO;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Brand;
use App\Models\User;
use App\Repositories\Brand\BrandRepository;

class UpdateBrandAction
{
    public function __construct(
        private BrandRepository $brandRepository,
    ) {}

    public function execute(
        UpdateBrandDTO $dto,
        User $user,
    ): Brand {
        if (!$user->hasRole('super_admin')
            && !$user->stores()->where('store_id', $dto->storeId)->exists()
        ) {
            throw new UnauthorizedStoreAccessException();
        }

        $brand = $this->brandRepository->findByIdOrFail(
            id:      $dto->brandId,
            storeId: $dto->storeId,
        );

        return $this->brandRepository->update(
            brand:       $brand,
            name:        $dto->name,
            slug:        $dto->slug,
            description: $dto->description,
            logoUrl:     $dto->logoUrl,
            sortOrder:   $dto->sortOrder,
            isActive:    $dto->isActive,
        );
    }
}
