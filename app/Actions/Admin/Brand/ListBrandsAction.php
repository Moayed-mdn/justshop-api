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
        User $user,
    ): LengthAwarePaginator {
        if (!$user->hasRole('super_admin')
            && !$user->stores()->where('store_id', $dto->storeId)->exists()
        ) {
            throw new UnauthorizedStoreAccessException();
        }

        return $this->brandRepository->paginate(
            storeId:  $dto->storeId,
            isActive: $dto->isActive,
            perPage:  $dto->perPage,
        );
    }
}
