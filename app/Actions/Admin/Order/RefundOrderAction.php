<?php

namespace App\Actions\Admin\Order;

use App\DTOs\Admin\Order\RefundOrderDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\RoleEnum;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Order;
use App\Repositories\Admin\Order\AdminOrderRepository;
use Illuminate\Support\Facades\Auth;

class RefundOrderAction
{
    public function __construct(
        private AdminOrderRepository $repository,
    ) {}

    public function execute(RefundOrderDTO $dto): Order
    {
        $order = $this->repository->findInStore($dto->orderId, $dto->storeId);
        
        // Logic for refund (e.g. Stripe refund, status update)
        $order->update(['status' => OrderStatusEnum::REFUNDED]);
        
        return $order->fresh();
    }
}
