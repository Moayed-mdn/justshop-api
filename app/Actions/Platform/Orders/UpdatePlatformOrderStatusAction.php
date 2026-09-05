<?php

declare(strict_types=1);

namespace App\Actions\Platform\Orders;

use App\DTOs\Platform\Orders\UpdatePlatformOrderStatusDTO;
use App\Models\Order;
use App\Repositories\Platform\Order\PlatformOrderRepository;

class UpdatePlatformOrderStatusAction
{
    public function __construct(
        private PlatformOrderRepository $repository,
    ) {}

    public function execute(UpdatePlatformOrderStatusDTO $dto): Order
    {
        $order = $this->repository->find($dto->orderId);

        return $this->repository->updateStatus($order, $dto->status);
    }
}
