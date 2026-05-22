<?php

namespace App\Actions\Admin\Dashboard;

use App\DTOs\Admin\Dashboard\GetRecentOrdersDTO;
use App\Repositories\Admin\Dashboard\AdminDashboardRepository;
use Illuminate\Support\Collection;

class GetRecentOrdersAction
{
    public function __construct(
        private AdminDashboardRepository $repository,
    ) {}

    public function execute(GetRecentOrdersDTO $dto): Collection
    {
        return $this->repository->getRecentOrders(
            storeId: $dto->storeId,
            limit: $dto->limit,
        );
    }
}
