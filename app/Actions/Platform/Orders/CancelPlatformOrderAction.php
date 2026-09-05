<?php

declare(strict_types=1);

namespace App\Actions\Platform\Orders;

use App\DTOs\Platform\Orders\CancelPlatformOrderDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use App\Repositories\Platform\Order\PlatformOrderRepository;

class CancelPlatformOrderAction
{
    public function __construct(
        private PlatformOrderRepository $repository,
    ) {}

    public function execute(CancelPlatformOrderDTO $dto): Order
    {
        $order = $this->repository->find($dto->orderId);

        return $this->repository->updateStatus($order, OrderStatusEnum::CANCELLED);
    }
}
