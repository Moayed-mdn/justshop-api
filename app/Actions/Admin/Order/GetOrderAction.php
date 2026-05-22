<?php

namespace App\Actions\Admin\Order;

use App\DTOs\Admin\Order\GetOrderDTO;
use App\Enums\RoleEnum;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Order;
use App\Repositories\Admin\Order\AdminOrderRepository;
use Illuminate\Support\Facades\Auth;

class GetOrderAction
{
    public function __construct(
        private AdminOrderRepository $repository,
    ) {}

    public function execute(GetOrderDTO $dto): Order
    {
        return $this->repository->findInStore($dto->orderId, $dto->storeId)
            ->load(['user', 'items.product', 'shippingAddress', 'billingAddress']);
    }
}
