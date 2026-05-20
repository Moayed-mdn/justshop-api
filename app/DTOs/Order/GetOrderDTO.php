<?php

declare(strict_types=1);

namespace App\DTOs\Order;

class GetOrderDTO
{
    public function __construct(
        public string $orderNumber,  // ← string, not int
        public int    $userId,
    ) {}

    public static function fromRequest(\App\Http\Requests\Order\GetOrderRequest $request): self
    {
        return new self(
            orderNumber: (string) $request->route('orderNumber'),  // ← keep as string
            userId:      (int) $request->user()->id,
        );
    }
}