<?php

declare(strict_types=1);

namespace App\Repositories\Product;

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository
{
    protected function modelClass(): string
    {
        return Product::class;
    }

    public function buildBaseQuery(int $storeId): Builder
    {
        $locale = app()->getLocale();

        return $this->scopedQuery()
            ->active()
            ->leftJoin('product_translations', function ($join) use ($locale) {
                $join->on('products.id', '=', 'product_translations.product_id')
                    ->where('product_translations.locale', $locale);
            })
            ->leftJoin('product_variants as display_v', function ($join) {
                $join->on('display_v.id', '=', \Illuminate\Support\Facades\DB::raw("(
                    SELECT id FROM product_variants 
                    WHERE product_id = products.id 
                    ORDER BY (CASE WHEN id = products.product_variant_id THEN 0 ELSE 1 END), id ASC 
                    LIMIT 1
                )"));
            })
            ->leftJoin('images', function ($join) {
                // ⚠️ FIX: Use subquery instead of direct join to prevent duplicate rows
                // when multiple is_primary=true images exist for the same variant.
                // ORDER BY id DESC ensures we get the most recently uploaded primary image.
                $join->on('images.id', '=', \Illuminate\Support\Facades\DB::raw("(
                    SELECT id FROM images 
                    WHERE imageable_id = display_v.id 
                    AND imageable_type = 'App\\\\Models\\\\ProductVariant' 
                    AND is_primary = 1 
                    ORDER BY id DESC 
                    LIMIT 1
                )"));
            })
            ->select(
                'products.id as product_id',
                'products.category_id',
                'display_v.id as product_variant_id',
                'product_translations.slug as slug',
                'product_translations.name as product_name',
                'product_translations.description as description',
                'display_v.price as price',
                'images.image_url as primary_image'
            );
    }

    public function getFilterRanges(Builder $query): object
    {
        $filterQuery = clone $query;
        $productIdsSub = $filterQuery->select('products.id');

        $variantStats = \Illuminate\Support\Facades\DB::table('product_variants')
            ->whereIn('product_id', $productIdsSub)
            ->selectRaw("
                MIN(price) AS min_price,
                MAX(price) AS max_price,
                MIN(manufacture_date) AS earliest_manufacture,
                MAX(expiry_date) AS latest_expiry
            ")->first();

        $ratingStats = \Illuminate\Support\Facades\DB::table('reviews')
            ->whereIn('product_id', function ($sub) use ($productIdsSub) {
                $sub->select('id')->from('products')
                    ->whereIn('products.id', $productIdsSub);
            })
            ->where('is_approved', true)
            ->selectRaw("MIN(rating) AS min_rating, MAX(rating) AS max_rating")
            ->first();

        $variantStats->min_rating = $ratingStats?->min_rating;
        $variantStats->max_rating = $ratingStats?->max_rating;

        return $variantStats;
    }

    public function applyPriceFilter(Builder $query, ?float $minPrice, ?float $maxPrice): Builder
    {
        if ($minPrice !== null) {
            $query->whereHas('variants', function ($q) use ($minPrice) {
                $q->where('price', '>=', $minPrice);
            });
        }

        if ($maxPrice !== null) {
            $query->whereHas('variants', function ($q) use ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            });
        }

        return $query;
    }

    public function applyManufactureFilter(Builder $query, ?string $earliestManufacture): Builder
    {
        if ($earliestManufacture !== null) {
            $query->whereHas('variants', function ($q) use ($earliestManufacture) {
                $q->where('manufacture_date', '>=', $earliestManufacture);
            });
        }

        return $query;
    }

    public function applyExpiryFilter(Builder $query, ?string $latestExpiry): Builder
    {
        if ($latestExpiry !== null) {
            $query->whereHas('variants', function ($q) use ($latestExpiry) {
                $q->where('expiry_date', '>=', $latestExpiry);
            });
        }

        return $query;
    }

    public function filterByCategory(Builder $query, array $categoryIds): Builder
    {
        return $query->whereIn('products.category_id', $categoryIds);
    }

    public function paginate(Builder $query, int $perPage): LengthAwarePaginator
    {
        return $query->paginate($perPage);
    }

    public function search(string $term, int $limit): Collection
    {
        return $this->scopedQuery()
            ->active()
            ->where(function (Builder $q) use ($term) {
                $q->whereHas('translations', function (Builder $q2) use ($term) {
                    $q2->where('product_translations.name', 'LIKE', "%{$term}%")
                       ->orWhere('product_translations.description', 'LIKE', "%{$term}%");
                })
                ->orWhereHas('brand', function (Builder $q2) use ($term) {
                    $q2->where('brands.name', 'LIKE', "%{$term}%");
                })
                ->orWhereHas('tags', function (Builder $q2) use ($term) {
                    $q2->whereHas('translations', function (Builder $q3) use ($term) {
                        $q3->where('tag_translations.name', 'LIKE', "%{$term}%");
                    });
                });
            })
            ->with([
                'translations',
                'defaultVariant.images',
                'variants' => fn ($q) => $q->where('is_active', true)->with('images'),
                'brand',
                'category.translations',
                'approvedReviews',
            ])
            ->limit($limit)
            ->get();
    }

    public function searchForAutocomplete(string $query, string $locale, int $limit): Collection
    {
        $storeId = $this->getCurrentStoreId();
        $term = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $query);

        $productIds = ProductTranslation::where('locale', $locale)
            ->where('name', 'LIKE', "%{$term}%")
            ->whereHas('product', fn (Builder $q) => $q
                ->where('store_id', $storeId)
                ->active()
            )
            ->orderByRaw("
                CASE
                    WHEN name = ? THEN 1
                    WHEN name LIKE ? THEN 2
                    ELSE 3
                END
            ", [$query, "{$query}%"])
            ->limit($limit)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return new Collection();
        }

        return $this->scopedQuery()
            ->whereIn('id', $productIds)
            ->with([
                'translations',
                'defaultVariant.images',
                'variants' => fn ($q) => $q->where('is_active', true)->with('images'),
                'approvedReviews',
            ])
            ->get();
    }

    public function findById(int $id, int $storeId): ?Product
    {
        return Product::where('store_id', $storeId)->find($id);
    }

    public function findBySlug(string $slug, int $storeId): ?Product
    {
        return Product::where('store_id', $storeId)->findBySlug($slug)->first();
    }

    public function findRelatedProducts(Product $currentProduct, int $storeId, int $limit = 8): Collection
    {
        $currentProduct->load(['category', 'tags']);

        $relatedQuery = Product::with([
            'translations',
            'activeVariants.images',
            'defaultVariant.images',
        ])
            ->where('store_id', $storeId)
            ->where('id', '!=', $currentProduct->id)
            ->where('is_active', true);

        if ($currentProduct->category_id) {
            $relatedQuery->where('category_id', $currentProduct->category_id);
        }

        if ($currentProduct->tags->isNotEmpty()) {
            $tagIds = $currentProduct->tags->pluck('id');
            $relatedQuery->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('product_tags.id', $tagIds);
            });
        }

        $relatedProducts = $relatedQuery
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        if ($relatedProducts->count() < 4) {
            $additionalProducts = Product::with([
                'translations',
                'activeVariants.images',
                'defaultVariant.images',
            ])
                ->where('store_id', $storeId)
                ->where('id', '!=', $currentProduct->id)
                ->where('is_active', true)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->inRandomOrder()
                ->limit($limit - $relatedProducts->count())
                ->get();

            $relatedProducts = $relatedProducts->merge($additionalProducts);
        }

        return $relatedProducts;
    }
}
