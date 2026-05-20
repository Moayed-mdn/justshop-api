<?php

declare(strict_types=1);

namespace App\Repositories\Order;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository
{
    public function getUserOrders(int $userId): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $userId)
            ->with([
                'items.productVariant.images',
                'items.productVariant.product.translations',
                'shippingAddress',
                'billingAddress',
                'paymentMethod',
            ])
            ->latest()
            ->paginate(10);
    }

    public function findById(int $id): ?Order
    {
        return Order::query()
            ->with([
                'items.productVariant.images',
                'items.productVariant.product.translations',
                'shippingAddress',
                'billingAddress',
                'paymentMethod',
            ])
            ->find($id);
    }

    public function findByOrderNumber(string $orderNumber): ?Order  // ← ADD THIS
    {
        return Order::query()
            ->with([
                'items.productVariant.images',
                'items.productVariant.product.translations',
                'shippingAddress',
                'billingAddress',
                'paymentMethod',
            ])
            ->where('order_number', $orderNumber)
            ->first();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order->fresh();
    }

    public function cancel(Order $order): Order
    {
        $order->update([
            'status'         => 'cancelled',
            'payment_status' => 'refunded',
            'cancelled_at'   => now(),
        ]);
        return $order->fresh();
    }

    public function restoreProductVariants(Order $order): void
    {
        foreach ($order->items as $item) {
            $item->productVariant->increment('quantity', $item->quantity);
        }
    }

    public function filter(\App\DTOs\Order\FilterOrdersDTO $dto): LengthAwarePaginator
    {
        $query = Order::query()->where('user_id', $dto->userId);

        if ($dto->status) {
            $query->where('status', $dto->status);
        }

        if ($dto->dateRange) {
            switch ($dto->dateRange) {
                case 'last_30_days':
                    $query->where('created_at', '>=', now()->subDays(30));
                    break;
                case 'last_6_months':
                    $query->where('created_at', '>=', now()->subMonths(6));
                    break;
                default:
                    if (is_numeric($dto->dateRange)) {
                        $query->whereYear('created_at', $dto->dateRange);
                    }
                    break;
            }
        }

        match ($dto->sortBy ?? 'date_desc') {
            'date_asc'  => $query->oldest(),
            default     => $query->latest(),
        };

        return $query->with([
            'items.productVariant.images',
            'items.productVariant.product.translations',
            'shippingAddress',
            'billingAddress',
            'paymentMethod',
        ])->paginate(10);
    }
}