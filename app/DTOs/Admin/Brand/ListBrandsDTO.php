<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Brand;

use App\Http\Requests\Admin\Brand\ListBrandsRequest;

class ListBrandsDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly ?bool $isActive,
        public readonly int $perPage,
    ) {}

    public static function fromRequest(
        ListBrandsRequest $request,
        int $storeId,
    ): self {
        return new self(
            storeId:  $storeId,
            isActive: $request->filled('is_active')
                ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
                : null,
            perPage: $request->integer('per_page', 20),
        );
    }
}
