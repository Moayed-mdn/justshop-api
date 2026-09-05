<?php

declare(strict_types=1);

namespace App\Repositories\Platform\Order;

use App\Enums\Order\OrderStatusEnum;
use App\Exceptions\Order\OrderNotFoundException;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Platform Order Repository
 *
 * Platform-scoped order access is intentionally cross-store: this
 * repository has no store scoping, unlike App\Repositories\Admin\Order\
 * AdminOrderRepository. Authorization for whether the caller is allowed
 * to be here at all lives entirely in App\Policies\PlatformOrderPolicy /
 * App\Enums\PermissionEnum::PLATFORM_ORDER_* -- this repository assumes
 * that check has already passed, consistent with the "Policies as Truth"
 * convention (repositories/actions never re-check permissions).
 */
class PlatformOrderRepository
{
    /**
     * List orders across all stores, with pagination.
     */
    public function list(?string $search = null, ?string $status = null, ?int $storeId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query();

        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with(['user', 'store', 'items.product'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find an order by id across any store, or throw.
     */
    public function find(int $orderId): Order
    {
        $order = Order::query()->find($orderId);

        if (!$order) {
            throw new OrderNotFoundException();
        }

        return $order;
    }

    public function updateStatus(Order $order, OrderStatusEnum $status): Order
    {
        $order->update(['status' => $status]);

        return $order->fresh();
    }
}
