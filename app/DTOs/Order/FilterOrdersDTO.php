<?php

namespace App\DTOs\Order;

use App\Http\Requests\Order\FilterOrdersRequest;

class FilterOrdersDTO
{
    public function __construct(
        public ?string $status,
        public ?string $dateRange,
        public ?string $sortBy,
        public int $userId
    ) {
    }

    public static function fromRequest(FilterOrdersRequest $request): self
    {
        return new self(
            status: $request->input('status'),
            dateRange: $request->input('date_range'),
            sortBy: $request->input('sort_by'),
            userId: $request->user()->id,
        );
    }
}