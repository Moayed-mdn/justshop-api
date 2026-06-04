<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

use App\Http\Requests\Checkout\CreateCheckoutRequest;
use App\Models\User;

class CreateCheckoutDTO
{
    public function __construct(
        public int $storeId,
        public ?User $user,
        public array $items = [],
        public ?string $email = null,
    ) {}

    public static function fromRequest(CreateCheckoutRequest $request, int $storeId): self
    {
        return new self(
            $storeId,
            $request->user('sanctum'),
            $request->input('items', []),
            $request->input('email'),
        );
    }
}
