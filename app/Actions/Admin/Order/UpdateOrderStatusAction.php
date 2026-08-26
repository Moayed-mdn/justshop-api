<?php

namespace App\Actions\Admin\Order;

use App\DTOs\Admin\Order\UpdateOrderStatusDTO;
use App\Enums\RoleEnum;
use App\Events\Order\OrderStatusChanged;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Order;
use App\Repositories\Admin\Order\AdminOrderRepository;
use Illuminate\Support\Facades\Auth;

class UpdateOrderStatusAction
{
    public function __construct(
        private AdminOrderRepository $repository,
    ) {}

    public function execute(UpdateOrderStatusDTO $dto): Order
    {
        $order = $this->repository->findInStore($dto->orderId, $dto->storeId);
        $previousStatus = $order->status;

        $order = $this->repository->updateStatus($order, $dto->status);

        // Cancellation has its own richer event/notification (OrderCancelled)
        // dispatched by CancelOrderAction; avoid a redundant/generic
        // "status changed" notification if this endpoint is ever used to
        // set CANCELLED directly.
        if ($previousStatus !== $order->status && $order->status !== \App\Enums\Order\OrderStatusEnum::CANCELLED) {
            OrderStatusChanged::dispatch($order->id, $previousStatus, $order->status);
        }

        return $order;
    }
}
