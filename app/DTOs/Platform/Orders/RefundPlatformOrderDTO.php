<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Orders;

use App\Http\Requests\Platform\Orders\RefundPlatformOrderRequest;

class RefundPlatformOrderDTO
{
    public function __construct(
        public int $orderId,
        public ?float $amount = null,
        public ?string $reason = null,
    ) {}

    public static function fromRequest(RefundPlatformOrderRequest $request, int $orderId): self
    {
        return new self(
            orderId: $orderId,
            amount: $request->has('amount') ? (float) $request->input('amount') : null,
            reason: $request->string('reason')->toString() ?: null,
        );
    }
}
