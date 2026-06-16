<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\DTOs\Order\GetOrderDTO;
use App\Models\Order;
use App\Repositories\Order\OrderRepository;

class GetOrderAction
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    public function execute(GetOrderDTO $dto): Order
    {
        // Wave 2 Remediation: Authorization removed from Action
        // Authorization now explicitly owned by OrderPolicy::view() in controller
        // This action is now orchestration-focused only
        
        $order = $this->orderRepository->findByOrderNumber($dto->orderNumber, $dto->storeId);

        if (!$order) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('error.order_not_found'));
        }

        return $order;
    }
}
