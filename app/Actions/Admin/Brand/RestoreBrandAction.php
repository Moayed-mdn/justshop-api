<?php

declare(strict_types=1);

namespace App\Actions\Admin\Brand;

use App\DTOs\Admin\Brand\RestoreBrandDTO;
use App\Exceptions\Brand\BrandNotFoundException;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Brand;
use App\Models\User;
use App\Repositories\Brand\BrandRepository;

class RestoreBrandAction
{
    public function __construct(
        private BrandRepository $brandRepository,
    ) {}

    public function execute(
        RestoreBrandDTO $dto,
    ): Brand {
        $brand = $this->brandRepository->findTrashedById(
            id:      $dto->brandId,
            storeId: $dto->storeId,
        );

        if ($brand === null || !$brand->trashed()) {
            throw new BrandNotFoundException();
        }

        $this->brandRepository->restore($brand);

        return $brand->fresh();
    }
}
