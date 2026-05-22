<?php

namespace App\Actions\Admin\Product;

use App\DTOs\Admin\Product\ListProductsDTO;
use App\Repositories\Admin\Product\AdminProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListProductsAction
{
    public function __construct(
        private AdminProductRepository $repository,
    ) {}

    public function execute(ListProductsDTO $dto): LengthAwarePaginator
    {
        return $this->repository->listForStore(
            storeId: $dto->storeId,
            search: $dto->search,
            status: $dto->status,
            perPage: $dto->perPage,
        );
    }
}
