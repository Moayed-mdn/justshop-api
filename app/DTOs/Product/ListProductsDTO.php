<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use App\Http\Requests\Product\FilterProductsRequest;

class ListProductsDTO
{
    public function __construct(
        public int $storeId,
        public ?string $categorySlug = null,
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public ?string $earliestManufacture = null,
        public ?string $latestExpiry = null,
        public array $brandSlugs = [],
        public ?float $minRating = null,
        public int $perPage = 20,
    ) {}

    public static function fromRequest(FilterProductsRequest $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            categorySlug: $request->string('category_slug')->toString() ?: null,
            minPrice: $request->filled('min_price') ? (float) $request->input('min_price') : null,
            maxPrice: $request->filled('max_price') ? (float) $request->input('max_price') : null,
            earliestManufacture: $request->string('earliest_manufacture')->toString() ?: null,
            latestExpiry: $request->string('latest_expiry')->toString() ?: null,
            brandSlugs: $request->input('brand_slugs', []),
            minRating: $request->filled('min_rating') ? (float) $request->input('min_rating') : null,
            perPage: $request->integer('per_page', 20),
        );
    }
}
