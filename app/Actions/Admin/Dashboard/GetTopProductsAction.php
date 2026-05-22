<?php

namespace App\Actions\Admin\Dashboard;

use App\DTOs\Admin\Dashboard\GetTopProductsDTO;
use App\Repositories\Admin\Dashboard\AdminDashboardRepository;
use Illuminate\Support\Collection;

class GetTopProductsAction
{
    public function __construct(
        private AdminDashboardRepository $repository,
    ) {}

    public function execute(GetTopProductsDTO $dto): Collection
    {
        return $this->repository->getTopProducts(
            storeId: $dto->storeId,
            limit: $dto->limit,
        );
    }
}
