<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\DTOs\Order\GetOrderDTO;
use App\Models\Order;
use App\Repositories\Order\OrderRepository;
use Illuminate\Auth\Access\AuthorizationException;

class GetOrderAction
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    public function execute(GetOrderDTO $dto): Order
    {
        $order = $this->orderRepository->findByOrderNumber($dto->orderNumber); // ← use orderNumber

        if (!$order || (int) $order->user_id !== $dto->userId) {
            throw new AuthorizationException(__('error.unauthorized_order_access'));
        }

        return $order;
    }
}