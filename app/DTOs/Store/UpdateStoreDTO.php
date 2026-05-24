<?php

namespace App\DTOs\Store;

use App\Http\Requests\Store\UpdateStoreRequest;

class UpdateStoreDTO
{
    public function __construct(
        public int $storeId,
        public ?string $name,
        public ?string $slug,
        public ?string $domain,
        public ?string $currency,
        public ?string $timezone,
        public ?bool $isActive,
    ) {}

    public static function fromRequest(UpdateStoreRequest $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            name: $request->has('name') ? $request->string('name')->toString() : null,
            slug: $request->has('slug') ? $request->string('slug')->toString() : null,
            domain: $request->has('domain') ? ($request->input('domain') !== null ? $request->string('domain')->toString() : null) : null,
            currency: $request->has('currency') ? $request->string('currency')->toString() : null,
            timezone: $request->has('timezone') ? $request->string('timezone')->toString() : null,
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
        );
    }
}
