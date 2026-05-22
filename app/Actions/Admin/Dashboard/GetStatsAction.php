<?php

namespace App\Actions\Admin\Dashboard;

use App\DTOs\Admin\Dashboard\GetStatsDTO;
use App\Repositories\Admin\Dashboard\AdminDashboardRepository;

class GetStatsAction
{
    public function __construct(
        private AdminDashboardRepository $repository,
    ) {}

    public function execute(GetStatsDTO $dto): array
    {
        return [
            'total_revenue'      => (float) $this->repository->getTotalRevenue($dto->storeId),
            'total_orders'       => (int)   $this->repository->getTotalOrders($dto->storeId),
            'total_customers'    => (int)   $this->repository->getTotalCustomers($dto->storeId),
            'total_products'     => (int)   $this->repository->getTotalProducts($dto->storeId),
            'revenue_change'     => 0.0,
            'orders_change'      => 0.0,
            'customers_change'   => 0.0,
            'products_change'    => 0.0,
        ];
    }
}
