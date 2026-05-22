<?php

declare(strict_types=1);

namespace App\Actions\Admin\Brand;

use App\DTOs\Admin\Brand\ShowBrandDTO;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Brand;
use App\Models\User;
use App\Repositories\Brand\BrandRepository;

class ShowBrandAction
{
    public function __construct(
        private BrandRepository $brandRepository,
    ) {}

    public function execute(
        ShowBrandDTO $dto,
    ): Brand {
        return $this->brandRepository->findByIdOrFail(
            id:      $dto->brandId,
            storeId: $dto->storeId,
        );
    }
}
