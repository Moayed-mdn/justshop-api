<?php

declare(strict_types=1);

namespace App\Actions\Admin\Brand;

use App\DTOs\Admin\Brand\ListBrandsDTO;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\User;
use App\Repositories\Brand\BrandRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListBrandsAction
{
    public function __construct(
        private BrandRepository $brandRepository,
    ) {}

    public function execute(
        ListBrandsDTO $dto,
    ): LengthAwarePaginator {
        return $this->brandRepository->paginate(
            storeId:  $dto->storeId,
            isActive: $dto->isActive,
            perPage:  $dto->perPage,
        );
    }
}
