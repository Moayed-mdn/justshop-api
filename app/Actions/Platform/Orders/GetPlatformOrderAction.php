<?php

declare(strict_types=1);

namespace App\Actions\Platform\Orders;

use App\DTOs\Platform\Orders\GetPlatformOrderDTO;
use App\Models\Order;
use App\Repositories\Platform\Order\PlatformOrderRepository;

class GetPlatformOrderAction
{
    public function __construct(
        private PlatformOrderRepository $repository,
    ) {}

    public function execute(GetPlatformOrderDTO $dto): Order
    {
        return $this->repository->find($dto->orderId)
            ->load(['user', 'store', 'items.product']);
    }
}
