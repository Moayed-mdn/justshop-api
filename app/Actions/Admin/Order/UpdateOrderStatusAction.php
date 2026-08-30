<?php

namespace App\Actions\Admin\Order;

use App\DTOs\Admin\Order\UpdateOrderStatusDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\RoleEnum;
use App\Events\Order\OrderStatusChanged;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Order;
use App\Repositories\Admin\Order\AdminOrderRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateOrderStatusAction
{
    public function __construct(
        private AdminOrderRepository $repository,
    ) {}

    public function execute(UpdateOrderStatusDTO $dto): Order
    {
        // Enforce store scoping outside the transaction (throws as before).
        $order = $this->repository->findInStore($dto->orderId, $dto->storeId);

        return DB::transaction(function () use ($order, $dto) {
            // Row lock: without this, two concurrent requests can both
            // read the same previousStatus before either commits, both
            // compute "changed", and both dispatch OrderStatusChanged —
            // producing duplicate notifications for one real change.
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $lockedOrder->status;

            $lockedOrder = $this->repository->updateStatus($lockedOrder, $dto->status);

            // Cancellation has its own richer event/notification (OrderCancelled)
            // dispatched by CancelOrderAction; avoid a redundant/generic
            // "status changed" notification if this endpoint is ever used to
            // set CANCELLED directly.
            if ($previousStatus !== $lockedOrder->status && $lockedOrder->status !== OrderStatusEnum::CANCELLED) {
                OrderStatusChanged::dispatch($lockedOrder->id, $previousStatus, $lockedOrder->status);
            }

            return $lockedOrder;
        });
    }
}