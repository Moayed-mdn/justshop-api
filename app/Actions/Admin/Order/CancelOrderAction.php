<?php

namespace App\Actions\Admin\Order;

use App\DTOs\Admin\Order\CancelOrderDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\RoleEnum;
use App\Events\Order\OrderCancelled;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Order;
use App\Repositories\Admin\Order\AdminOrderRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CancelOrderAction
{
    public function __construct(
        private AdminOrderRepository $repository,
    ) {}

    public function execute(CancelOrderDTO $dto): Order
    {
        // Resolve the order and enforce store scoping outside the
        // transaction (throws OrderNotFoundException as before).
        $order = $this->repository->findInStore($dto->orderId, $dto->storeId);

        return DB::transaction(function () use ($order) {
            // Row lock + idempotency check: previously this action had
            // neither a transaction nor a status guard, so calling it
            // twice for the same order (double-click, retried request)
            // always dispatched OrderCancelled twice, producing duplicate
            // notifications for one real cancellation.
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === OrderStatusEnum::CANCELLED) {
                // Already cancelled (by this request or a concurrent one) — no-op.
                return $lockedOrder;
            }

            $lockedOrder->update(['status' => OrderStatusEnum::CANCELLED]);
            $lockedOrder = $lockedOrder->fresh();

            OrderCancelled::dispatch($lockedOrder->id, (int) Auth::id());

            return $lockedOrder;
        });
    }
}