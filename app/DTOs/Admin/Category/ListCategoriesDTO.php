<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Category;

use App\Http\Requests\Admin\Category\ListCategoriesRequest;

class ListCategoriesDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly ?int $parentId,
        public readonly ?bool $isActive,
        public readonly int $perPage,
    ) {}

    public static function fromRequest(
        ListCategoriesRequest $request,
        int $storeId,
    ): self {
        return new self(
            storeId:  $storeId,
            parentId: $request->filled('parent_id')
                ? $request->integer('parent_id')
                : null,
            isActive: $request->filled('is_active')
                ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
                : null,
            perPage: $request->integer('per_page', 20),
        );
    }
}
