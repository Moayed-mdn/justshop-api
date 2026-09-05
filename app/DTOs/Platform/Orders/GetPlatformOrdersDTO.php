<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Orders;

use App\Http\Requests\Platform\Orders\GetPlatformOrdersRequest;

class GetPlatformOrdersDTO
{
    public function __construct(
        public ?string $status = null,
        public ?string $search = null,
        public ?int $storeId = null,
        public int $perPage = 15,
        public int $page = 1,
    ) {}

    public static function fromRequest(GetPlatformOrdersRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString() ?: null,
            search: $request->string('search')->toString() ?: null,
            storeId: $request->integer('store_id') ?: null,
            perPage: $request->integer('per_page', 15),
            page: $request->integer('page', 1),
        );
    }
}
