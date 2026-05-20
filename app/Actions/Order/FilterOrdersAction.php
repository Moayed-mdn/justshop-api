<?php

namespace App\Actions\Order;

use App\DTOs\Order\FilterOrdersDTO;
use App\Repositories\Order\OrderRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class FilterOrdersAction
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    public function execute(FilterOrdersDTO $dto): LengthAwarePaginator
    {
        return $this->orderRepository->filter($dto);
    }
}