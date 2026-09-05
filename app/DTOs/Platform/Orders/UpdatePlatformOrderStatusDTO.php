<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Orders;

use App\Enums\Order\OrderStatusEnum;
use App\Http\Requests\Platform\Orders\UpdatePlatformOrderStatusRequest;

class UpdatePlatformOrderStatusDTO
{
    public function __construct(
        public int $orderId,
        public OrderStatusEnum $status,
    ) {}

    public static function fromRequest(UpdatePlatformOrderStatusRequest $request, int $orderId): self
    {
        return new self(
            orderId: $orderId,
            status: OrderStatusEnum::from($request->string('status')->toString()),
        );
    }
}
