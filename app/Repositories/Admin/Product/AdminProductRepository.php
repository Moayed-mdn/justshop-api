<?php

namespace App\Repositories\Admin\Product;

use App\Enums\Product\ProductStatusEnum;
use App\Exceptions\Product\ProductNotFoundException;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Support\Media\MediaUrl;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminProductRepository
{
    // ── Eager-load Definitions ─────────────────────────────────

    /**
     * Relations for the admin product editor response.
     *
     * ── Media (dual-layer architecture) ───────────────────────
     * 'images'          → Product-level shared gallery.
     *                     imageable_type = App\Models\Product.
     * 'variants.images' → Variant-level specific media.
     *                     imageable_type = App\Models\ProductVariant.
     *
     * ── Option system (new) ────────────────────────────────────
     * 'productOptions.values'        → Canonical option definitions.
     * 'variants.optionValues.option' → Per-variant option value assignments
     *                                  via variant_option_values pivot.
     *
     * ── Attribute system (legacy — bridge period) ─────────────
     * Removed in Phase 8.
     * Storefront now uses the new option system.
     *
     * ── Tags ──────────────────────────────────────────────────
     * 'tags.translations' → Tag metadata + all locale translations.
     *                       Required by AdminProductDetailResource::buildTags().
     *                       name and slug live in tag_translations, NOT tags.
     *                       Loading translations here prevents N+1 in resource.
     *
     * ── Common ────────────────────────────────────────────────
     * 'category', 'translations'
     */
    private function editorRelations(): array
    {
        return [
            // ── Product-level media ────────────────────────────
            'images',

            // ── New option system ──────────────────────────────
            'productOptions.values',
            'variants.optionValues.option',

            // ── Variant-level media ────────────────────────────
            'variants.images',

            'defaultVariant',

            // ── Common ─────────────────────────────────────────
            'category',
            'translations',

            // ── Tags with translations ─────────────────────────
            // name and slug live in tag_translations, not tags table.
            // Must load translations here to avoid N+1 in buildTags().
            'tags.translations',
        ];
    }

    // ── Read Operations ────────────────────────────────────────

    public function listForStore(
        int $storeId,
        ?string $search = null,
        ?string $status = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = Product::query()
            ->where('store_id', $storeId);

        if ($search) {
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($status === ProductStatusEnum::ACTIVE->value) {
            $query->where('is_active', true);
        } elseif ($status === ProductStatusEnum::INACTIVE->value) {
            $query->where('is_active', false);
        }

        // List view loads a lighter relation set than the editor.
        // Product-level images included so getPrimaryImageUrlAttribute
        // resolves without N+1.
        // Tags not included in list view — not needed for listing UI.
        return $query
            ->with([
                'category',
                'defaultVariant',
                'images',
                'variants',
                'variants.images',
                'translations',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function findInStore(int $productId, int $storeId): Product
    {
        $product = Product::query()
            ->where('store_id', $storeId)
            ->where('id', $productId)
            ->first();

        if (!$product) {
            throw new ProductNotFoundException();
        }

        return $product;
    }

    public function findEditorProductInStore(int $productId, int $storeId): Product
    {
        $product = Product::query()
            ->with($this->editorRelations())
            ->where('store_id', $storeId)
            ->where('id', $productId)
            ->first();

        if (!$product) {
            throw new ProductNotFoundException();
        }

        return $product;
    }

    public function findTrashedInStore(int $productId, int $storeId): ?Product
    {
        return Product::withTrashed()
            ->where('store_id', $storeId)
            ->where('id', $productId)
            ->first();
    }

    /**
     * Reload a product with all admin editor relations after a write operation.
     */
    public function refreshEditorProduct(Product $product): Product
    {
        return $product->fresh($this->editorRelations())
            ?? $product->load($this->editorRelations());
    }

    // ── Product Write Operations ───────────────────────────────

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function softDelete(Product $product): void
    {
        $product->delete();
    }

    public function restore(Product $product): Product
    {
        $product->restore();
        return $product->fresh();
    }

    // ── Translation Operations ─────────────────────────────────

    public function createTranslation(Product $product, array $translationData): void
    {
        $product->translations()->create($translationData);
    }

    public function upsertTranslation(
        Product $product,
        string $locale,
        array $translationData,
    ): void {
        $product->translations()->updateOrCreate(
            ['locale' => $locale],
            $translationData,
        );
    }

    public function deleteTranslations(Product $product): void
    {
        $product->translations()->delete();
    }

    // ── Tag Operations ─────────────────────────────────────────

    /**
     * Sync tag associations for a product.
     *
     * Accepts an array of integer tag IDs.
     * Laravel's sync() detaches removed IDs and attaches new ones atomically.
     * Passing an empty array detaches all tags from the product.
     *
     * Store-scope validation: the caller (Action layer) is responsible for
     * ensuring all tag IDs belong to the correct store. Request validation
     * confirms existence in the tags table via exists:tags,id.
     *
     * @param  int[]  $tagIds
     */
    public function syncTags(Product $product, array $tagIds): void
    {
        $product->tags()->sync($tagIds);
    }

    // ── Variant Operations ─────────────────────────────────────

    public function createVariant(Product $product, array $variantData): ProductVariant
    {
        return $product->variants()->create($variantData);
    }

    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->update($data);
        return $variant->refresh();
    }

    public function deleteVariant(ProductVariant $variant): void
    {
        $variant->delete();
    }

    // ── Media Operations ───────────────────────────────────────

    /**
     * Create product-level media items.
     *
     * Attaches images directly to the Product (imageable = Product).
     * These represent the shared product gallery.
     *
     * Maps API `position` → DB `sort_order`.
     * First item receives is_primary = true.
     *
     * @param  array  $mediaItems  [['url' => '...', 'alt' => '...', 'position' => 0], ...]
     */
    public function createProductMedia(Product $product, array $mediaItems): void
    {
        foreach ($mediaItems as $index => $mediaData) {
            $product->images()->create([
                'image_url'  => $this->normalizeImagePath($mediaData['url']),
                'alt_text'   => $mediaData['alt'] ?? null,
                'sort_order' => $mediaData['position'] ?? $index,
                'is_primary' => $index === 0,
            ]);
        }
    }

    /**
     * Create variant-level media items.
     *
     * Attaches images directly to the ProductVariant (imageable = ProductVariant).
     * These represent variant-specific visuals.
     *
     * Maps API `position` → DB `sort_order`.
     * First item receives is_primary = true.
     *
     * @param  array  $mediaItems  [['url' => '...', 'alt' => '...', 'position' => 0], ...]
     */
    public function createVariantMedia(ProductVariant $variant, array $mediaItems): void
    {
        foreach ($mediaItems as $index => $mediaData) {
            $variant->images()->create([
                'image_url'  => $this->normalizeImagePath($mediaData['url']),
                'alt_text'   => $mediaData['alt'] ?? null,
                'sort_order' => $mediaData['position'] ?? $index,
                'is_primary' => $index === 0,
            ]);
        }
    }

    /**
     * Normalize image path by stripping domain and /storage/ prefix.
     * Ensures we always store relative paths in the database.
     *
     * Examples:
     * - "http://localhost:8000/storage/variants/xyz.jpg" → "variants/xyz.jpg"
     * - "/storage/variants/xyz.jpg" → "variants/xyz.jpg"
     * - "variants/xyz.jpg" → "variants/xyz.jpg"
     * - "https://example.com/image.jpg" → "https://example.com/image.jpg" (external URL preserved)
     */
    private function normalizeImagePath(string $url): string
    {
        return (string) MediaUrl::normalizeStorablePath($url);
    }

    /**
     * Replace all product-level media using upsert-by-id semantics.
     */
    public function syncProductMedia(Product $product, array $mediaItems): void
    {
        $this->syncMediaFor($product->images(), $mediaItems);
    }

    /**
     * Replace all variant-level media using upsert-by-id semantics.
     */
    public function syncVariantMedia(ProductVariant $variant, array $mediaItems): void
    {
        $this->syncMediaFor($variant->images(), $mediaItems);
    }

    /**
     * Shared upsert-by-id sync logic for product- and variant-level media.
     *
     * - Items with a positive id matching an existing row are updated in place.
     * - Items without an id (or an id that isn't among the existing rows) are created.
     * - Existing rows whose id is not present in $mediaItems are deleted.
     * - The item at index 0 in the resulting order is marked primary (same rule as today).
     *
     * @param  array  $mediaItems  [['id' => 12, 'url' => '...', 'alt' => '...', 'position' => 0], ...]
     */
    private function syncMediaFor($relation, array $mediaItems): void
    {
        $existingIds = $relation->pluck('id')->all();
        $keepIds     = [];

        foreach ($mediaItems as $index => $mediaData) {
            $payload = [
                'image_url'  => $this->normalizeImagePath($mediaData['url']),
                'alt_text'   => $mediaData['alt'] ?? null,
                'sort_order' => $mediaData['position'] ?? $index,
                'is_primary' => $index === 0,
            ];

            $id = $mediaData['id'] ?? null;

            if ($id && in_array($id, $existingIds, true)) {
                $relation->where('id', $id)->update($payload);
                $keepIds[] = $id;
                continue;
            }

            $created = $relation->create($payload);
            $keepIds[] = $created->id;
        }

        $idsToDelete = array_diff($existingIds, $keepIds);

        if (!empty($idsToDelete)) {
            $relation->whereIn('id', $idsToDelete)->delete();
        }
    }

    // ── New Option System ──────────────────────────────────────

    /**
     * Sync canonical product options (new system).
     *
     * Creates or updates each option by name, syncs its allowed values,
     * and removes options/values that are no longer in the list.
     *
     * Returns a map of option name → ProductOption (with values loaded).
     *
     * @param  array  $options  [['name' => 'Color', 'position' => 1, 'values' => ['Red', 'Blue']], ...]
     * @return array<string, ProductOption>
     */
    public function syncProductOptions(Product $product, array $options): array
    {
        $optionMap     = [];
        $incomingNames = array_column($options, 'name');

        foreach ($options as $optionData) {
            // Bug #11 fix: restore soft-deleted options instead of creating duplicates.
            // There's a unique constraint on (product_id, name), so creating a new row
            // when a trashed one exists would cause a constraint violation.
            $option = $product->productOptions()
                ->withTrashed()
                ->where('name', $optionData['name'])
                ->first();

            if ($option) {
                if ($option->trashed()) {
                    $option->restore();
                }
                $option->update(['position' => $optionData['position']]);
            } else {
                $option = $product->productOptions()->create([
                    'name'     => $optionData['name'],
                    'position' => $optionData['position'],
                ]);
            }

            $incomingValues = $optionData['values'] ?? [];

            foreach ($incomingValues as $value) {
                // Same restore logic for option values - unique constraint on (option_id, value)
                $optionValue = $option->values()
                    ->withTrashed()
                    ->where('value', $value)
                    ->first();

                if ($optionValue) {
                    if ($optionValue->trashed()) {
                        $optionValue->restore();
                    }
                } else {
                    $option->values()->create(['value' => $value]);
                }
            }

            $option->values()
                ->whereNotIn('value', $incomingValues)
                ->delete();

            $optionMap[$optionData['name']] = $option->load('values');
        }

        $product->productOptions()
            ->whereNotIn('name', $incomingNames)
            ->delete();

        return $optionMap;
    }

    /**
     * Sync variant option value assignments (new system).
     *
     * @param  array<string, string>        $optionsMap
     * @param  array<string, ProductOption> $productOptionMap
     */
    public function syncVariantOptionValues(
        ProductVariant $variant,
        array $optionsMap,
        array $productOptionMap,
    ): void {
        $syncData = [];

        foreach ($optionsMap as $optionName => $value) {
            $option = $productOptionMap[$optionName] ?? null;

            if (!$option) {
                continue;
            }

            $optionValue = $option->values->firstWhere('value', $value);

            if (!$optionValue) {
                continue;
            }

            $syncData[$optionValue->id] = ['option_id' => $option->id];
        }

        $variant->optionValues()->sync($syncData);
    }


}
