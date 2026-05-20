<?php

declare(strict_types=1);

namespace App\DTOs\Order;

class CancelOrderDTO
{
    public function __construct(
        public string $orderNumber,  // ← string, not int
        public int    $userId,
    ) {}

    public static function fromRequest(\App\Http\Requests\Order\CancelOrderRequest $request): self
    {
        return new self(
            orderNumber: (string) $request->route('orderNumber'),  // ← keep as string
            userId:      (int) $request->user()->id,
        );
    }
}