<?php
// app/GraphQL/Queries/Search.php

namespace App\GraphQL\Queries;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Brand\BrandRepository;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Product\ProductRepository;

class Search
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
        $limit  = min($args['limit'] ?? 20, 50); // cap at 50

        if (mb_strlen($query) < 1) {
            return ['products' => [], 'categories' => [], 'brands' => [], 'total_count' => 0];
        }

        // Sanitize for LIKE
        $searchTerm = $this->sanitize($query);

        $products   = $this->searchProducts($searchTerm, $locale, $limit);
        $categories = $this->searchCategories($searchTerm, $locale, $limit);
        $brands     = $this->searchBrands($searchTerm, $limit);

        return [
            'products'    => $products,
            'categories'  => $categories,
            'brands'      => $brands,
            'total_count' => count($products) + count($categories) + count($brands),
        ];
    }

    // ── Products ───────────────────────────────────────────────

    private function searchProducts(string $term, string $locale, int $limit): array
    {
        $products = $this->productRepository->search($term, $limit);

        // Map & score for relevance
        return $products->map(function (Product $product) use ($term, $locale) {
            $translation  = $product->translation($locale);
            $variant      = $product->display_variant;
            $primaryImage = $this->resolveImage($product);

            return [
                'id'            => $product->id,
                'name'          => $translation?->name ?? '',
                'slug'          => $translation?->slug ?? '',
                'description'   => $translation?->description,
                'price'         => $variant?->price,
                'image_url'     => $primaryImage,
                'avg_rating'    => $product->avg_rating,
                'reviews_count' => $product->reviews_count,
                'category_name' => $product->category?->translation($locale)?->name,
                'brand_name'    => $product->brand?->name,
                'product_variant_id' => $variant?->id,
                'max_quantity' => $variant?->quantity,
                '_relevance'    => $this->scoreProduct($product, $term, $locale),
            ];
        })
        ->sortByDesc('_relevance')
        ->map(fn ($item) => collect($item)->except('_relevance')->all())
        ->values()
        ->all();
    }

    // ── Categories ─────────────────────────────────────────────

    private function searchCategories(string $term, string $locale, int $limit): array
    {
        return $this->categoryRepository->search($term, $limit)
            ->map(function (Category $category) use ($locale) {
                $translation = $category->translation($locale);

                return [
                    'id'             => $category->id,
                    'name'           => $translation?->name ?? '',
                    'slug'           => $translation?->slug ?? '',
                    'products_count' => $category->products_count,
                ];
            })
            ->sortByDesc('products_count')
            ->values()
            ->all();
    }

    // ── Brands ─────────────────────────────────────────────────

    private function searchBrands(string $term, int $limit): array
    {
        return $this->brandRepository->search($term, $limit)
            ->map(fn (Brand $brand) => [
                'id'             => $brand->id,
                'name'           => $brand->name,
                'slug'           => $brand->slug,
                'logo_url'       => $brand->logo_url,
                'products_count' => $brand->products_count,
            ])
            ->all();
    }

    // ── Relevance Scoring ──────────────────────────────────────

    private function scoreProduct(Product $product, string $term, string $locale): int
    {
        $score = 0;
        $termLower = mb_strtolower($term);

        // Check all translations for relevance
        foreach ($product->translations as $translation) {
            $name = mb_strtolower($translation->name);

            if ($name === $termLower) {
                $score = max($score, 100); // exact match
            } elseif (str_starts_with($name, $termLower)) {
                $score = max($score, 80);  // starts with
            } elseif (str_contains($name, $termLower)) {
                $score = max($score, 60);  // contains in name
            }

            $desc = mb_strtolower($translation->description ?? '');
            if (str_contains($desc, $termLower)) {
                $score = max($score, 40);  // in description
            }
        }

        // Brand name match bonus
        if ($product->brand && str_contains(mb_strtolower($product->brand->name), $termLower)) {
            $score = max($score, 50);
        }

        // Boost products with higher ratings
        if ($product->avg_rating) {
            $score += (int) ($product->avg_rating * 2); // up to +10
        }

        return $score;
    }

    // ── Helpers ────────────────────────────────────────────────

    private function resolveImage(Product $product): ?string
    {
        // Try default variant first
        $variant = $product->defaultVariant;

        if ($variant && $variant->relationLoaded('images')) {
            $img = $variant->images->where('is_primary', true)->first();
            if ($img) return $img->full_url;
        }

        // Fallback to first active variant
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

    private function sanitize(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }
}