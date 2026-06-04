<?php
// app/GraphQL/Queries/Autocomplete.php

namespace App\GraphQL\Queries;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Brand\BrandRepository;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Product\ProductRepository;

class Autocomplete
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly BrandRepository $brandRepository,
    ) {}

    public function __invoke($rootValue, array $args): array
    {
        $query  = trim($args['query']);
        $locale = $args['locale'] ?? 'en';
        $limit  = min($args['limit'] ?? 10, 20);

        if (mb_strlen($query) < 2) {
            return [];
        }

        $suggestions = collect();

        // ── Products (60% of slots) ────────────────────────────

        $productLimit = (int) ceil($limit * 0.6);

        $products = $this->productRepository->searchForAutocomplete($query, $locale, $productLimit);

        foreach ($products as $product) {
            $translation = $product->translation($locale);
            $variant     = $product->display_variant;
            $imageUrl    = $this->resolveImage($product);

            $suggestions->push([
                'id'            => (string) $product->id,
                'text'          => $translation?->name ?? '',
                'type'          => 'PRODUCT',
                'slug'          => $translation?->slug ?? '',
                'image_url'     => $imageUrl,
                'price'         => $variant?->price,
                'avg_rating'    => $product->avg_rating,
                'reviews_count' => $product->reviews_count,
            ]);
        }

        // ── Categories (25% of slots) ──────────────────────────

        $catLimit = (int) ceil($limit * 0.25);

        $categories = $this->categoryRepository->searchForAutocomplete($query, $locale, $catLimit)
            ->map(function (Category $category) use ($locale) {
                $t = $category->translation($locale);
                return [
                    'id'            => (string) $category->id,
                    'text'          => $t?->name ?? '',
                    'type'          => 'CATEGORY',
                    'slug'          => $t?->slug ?? '',
                    'image_url'     => null,
                    'price'         => null,
                    'avg_rating'    => null,
                    'reviews_count' => null,
                ];
            });

        $suggestions = $suggestions->merge($categories);

        // ── Brands (15% of slots) ──────────────────────────────

        $brandLimit = (int) ceil($limit * 0.15);

        $brands = $this->brandRepository->searchForAutocomplete($query, $brandLimit)
            ->map(fn (Brand $b) => [
                'id'            => (string) $b->id,
                'text'          => $b->name,
                'type'          => 'BRAND',
                'slug'          => $b->slug,
                'image_url'     => $b->logo_url,
                'price'         => null,
                'avg_rating'    => null,
                'reviews_count' => null,
            ]);

        $suggestions = $suggestions->merge($brands);

        return $suggestions->take($limit)->values()->all();
    }

    private function resolveImage(Product $product): ?string
    {
        $variant = $product->defaultVariant;

        if ($variant && $variant->relationLoaded('images')) {
            $img = $variant->images->where('is_primary', true)->first();
            if ($img) return $img->full_url;
        }

        if ($product->relationLoaded('variants')) {
            foreach ($product->variants as $v) {
                if (!$v->is_active) continue;
                if ($v->relationLoaded('images')) {
                    $img = $v->images->where('is_primary', true)->first();
                    if ($img) return $img->full_url;
                }
            }
        }

        return null;
    }
}