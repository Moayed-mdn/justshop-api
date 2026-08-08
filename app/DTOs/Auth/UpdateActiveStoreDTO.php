<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use App\Http\Requests\Auth\UpdateActiveStoreRequest;

class UpdateActiveStoreDTO
{
    public function __construct(
        public int $userId,
        public int $storeId,
    ) {}

    public static function fromRequest(UpdateActiveStoreRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            storeId: (int) $request->input('store_id'),
        );
    }
}
