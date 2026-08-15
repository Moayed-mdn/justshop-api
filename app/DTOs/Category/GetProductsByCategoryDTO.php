<?php

declare(strict_types=1);

namespace App\DTOs\Category;

use App\Http\Requests\Product\GetProductsByCategoryRequest;

class GetProductsByCategoryDTO
{
    public function __construct(
        public int $storeId,
        public string $slug,
        public ?string $categorySlug = null,
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public ?string $earliestManufacture = null,
        public ?string $latestExpiry = null,
        public ?float $minRating = null,
        public int $perPage = 20,
    ) {}

    public static function fromRequest(GetProductsByCategoryRequest $request): self
    {
        return new self(
            (int) $request->route('store')->id,
            (string) $request->route('slug'),
            $request->string('category_slug')->toString() ?: null,
            $request->filled('min_price') ? (float) $request->input('min_price') : null,
            $request->filled('max_price') ? (float) $request->input('max_price') : null,
            $request->string('earliest_manufacture')->toString() ?: null,
            $request->string('latest_expiry')->toString() ?: null,
            $request->filled('min_rating') ? (float) $request->input('min_rating') : null,
            $request->integer('per_page', 20),
        );
    }
}
