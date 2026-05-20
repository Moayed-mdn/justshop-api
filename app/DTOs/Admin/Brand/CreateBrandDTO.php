<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Brand;

use App\Http\Requests\Admin\Brand\CreateBrandRequest;

class CreateBrandDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $logoUrl,
        public readonly int $sortOrder,
        public readonly bool $isActive,
    ) {}

    public static function fromRequest(
        CreateBrandRequest $request,
        int $storeId,
    ): self {
        return new self(
            storeId:     $storeId,
            name:        $request->string('name')->toString(),
            slug:        $request->string('slug')->toString(),
            description: $request->filled('description')
                ? $request->string('description')->toString()
                : null,
            logoUrl:     $request->filled('logo_url')
                ? $request->string('logo_url')->toString()
                : null,
            sortOrder:   $request->integer('sort_order', 0),
            isActive:    (bool) $request->input('is_active', true),
        );
    }
}
