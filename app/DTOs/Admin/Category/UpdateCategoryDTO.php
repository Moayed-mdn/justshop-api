<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Category;

use App\Http\Requests\Admin\Category\UpdateCategoryRequest;

class UpdateCategoryDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly int $categoryId,
        public readonly string $slug,
        public readonly ?int $parentId,
        public readonly int $sortOrder,
        public readonly bool $isActive,
        public readonly array $translations,
    ) {}

    public static function fromRequest(
        UpdateCategoryRequest $request,
        int $storeId,
        int $categoryId,
    ): self {
        return new self(
            storeId:      $storeId,
            categoryId:   $categoryId,
            slug:         $request->string('slug')->toString(),
            parentId:     $request->filled('parent_id')
                ? $request->integer('parent_id')
                : null,
            sortOrder:    $request->integer('sort_order', 0),
            isActive:     (bool) $request->input('is_active', true),
            translations: $request->input('translations', []),
        );
    }
}
