<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\DTOs\Order\CancelOrderDTO;
use App\Models\Order;
use App\Repositories\Order\OrderRepository;
use App\Services\OrderService;
use Illuminate\Auth\Access\AuthorizationException;

class CancelOrderAction
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderService    $orderService,
    ) {}

    public function execute(CancelOrderDTO $dto): Order
    {
        $order = $this->orderRepository->findByOrderNumber($dto->orderNumber); // ← use orderNumber

        if (!$order || (int) $order->user_id !== $dto->userId) {
            throw new AuthorizationException(__('error.unauthorized_order_access'));
        }

        return $this->orderService->cancelOrder($order);
    }
}