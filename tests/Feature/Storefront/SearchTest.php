<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers App\Http\Controllers\Api\Storefront\SearchController::index() via
 * the real HTTP route registered in routes/api/v1/storefront/search.php:
 *
 *   GET /api/v1/storefront/stores/{store}/search
 *
 * This route sits behind `store.context` only (no auth:sanctum in its
 * middleware stack), so search is accessible to guests — tests below do not
 * authenticate.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(Store $store, string $name, bool $isActive = true, ?string $sku = null): Product
    {
        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => null,
            'brand_id' => null,
            'product_variant_id' => null,
            'is_active' => $isActive,
        ]);

        $product->translations()->create([
            'locale' => 'en',
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . $product->id,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $sku ?? ('SKU-' . $product->id),
            'price' => 10,
            'quantity' => 5,
            'is_active' => true,
        ]);

        return $product;
    }

    private function makeCategory(Store $store, string $name, bool $isActive = true): Category
    {
        $category = Category::factory()->create(['store_id' => $store->id, 'is_active' => $isActive]);
        $category->translations()->create([
            'locale' => 'en',
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . $category->id,
        ]);

        return $category;
    }

    // ── Happy path ───────────────────────────────────────────────

    public function test_search_with_default_type_returns_matching_products_and_categories(): void
    {
        $store = Store::factory()->create();
        $this->makeProduct($store, 'Blue Running Shoes');
        $this->makeCategory($store, 'Running Gear');

        $response = $this->getJson("/api/v1/storefront/stores/{$store->id}/search?query=Running");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'all')
            ->assertJsonCount(1, 'data.products')
            ->assertJsonCount(1, 'data.categories');
    }

    public function test_search_type_products_returns_paginated_matches(): void
    {
        $store = Store::factory()->create();
        $this->makeProduct($store, 'Blue Running Shoes');
        $this->makeProduct($store, 'Red Running Shoes');
        $this->makeProduct($store, 'Winter Coat');

        $response = $this->getJson("/api/v1/storefront/stores/{$store->id}/search?query=Running&type=products");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.type', 'products')
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_search_type_categories_returns_paginated_matches(): void
    {
        $store = Store::factory()->create();
        $this->makeCategory($store, 'Running Gear');
        $this->makeCategory($store, 'Winter Gear');

        $response = $this->getJson("/api/v1/storefront/stores/{$store->id}/search?query=Running&type=categories");

        $response->assertStatus(200)
            ->assertJsonPath('meta.type', 'categories')
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_search_matches_products_by_variant_sku(): void
    {
        $store = Store::factory()->create();
        $this->makeProduct($store, 'Unrelated Name', true, 'ZX-9000-SPECIAL');

        $response = $this->getJson("/api/v1/storefront/stores/{$store->id}/search?query=ZX-9000&type=products");

        $response->assertJsonPath('meta.pagination.total', 1);
    }

    // ── Validation failures ──────────────────────────────────────

    public function test_search_without_query_fails_validation(): void
    {
        $store = Store::factory()->create();

        $this->getJson("/api/v1/storefront/stores/{$store->id}/search")
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function test_search_with_invalid_type_fails_validation(): void
    {
        $store = Store::factory()->create();

        $this->getJson("/api/v1/storefront/stores/{$store->id}/search?query=shoes&type=invalid")
            ->assertStatus(422);
    }

    public function test_search_with_limit_above_maximum_fails_validation(): void
    {
        $store = Store::factory()->create();

        $this->getJson("/api/v1/storefront/stores/{$store->id}/search?query=shoes&limit=500")
            ->assertStatus(422);
    }

    // ── Edge cases ────────────────────────────────────────────────

    public function test_search_does_not_return_products_from_another_store(): void
    {
        $store = Store::factory()->create();
        $otherStore = Store::factory()->create();
        $this->makeProduct($otherStore, 'Running Shoes From Another Store');

        $response = $this->getJson("/api/v1/storefront/stores/{$store->id}/search?query=Running&type=products");

        $response->assertJsonPath('meta.pagination.total', 0);
    }

    public function test_search_excludes_inactive_products(): void
    {
        $store = Store::factory()->create();
        $this->makeProduct($store, 'Discontinued Running Shoes', false);

        $response = $this->getJson("/api/v1/storefront/stores/{$store->id}/search?query=Running&type=products");

        $response->assertJsonPath('meta.pagination.total', 0);
    }

    public function test_search_type_products_response_fields_do_not_match_the_underlying_product_columns(): void
    {
        $store = Store::factory()->create();
        $product = $this->makeProduct($store, 'Traceable Product Name');

        $response = $this->getJson("/api/v1/storefront/stores/{$store->id}/search?query=Traceable&type=products");

        $response->assertStatus(200)->assertJsonPath('meta.pagination.total', 1);

        // NOTE: SearchController::index() serializes both `type=products`
        // and `type=categories` results through
        // App\Http\Resources\ProductCardResource::collection(), regardless
        // of which type was requested. ProductCardResource::toArray() reads
        // flattened "card" fields (product_id, slug, product_name, price,
        // primary_image, ...) that do not exist as columns/attributes on a
        // plain App\Models\Product (whose primary key is `id`, and whose
        // name/price/slug live on related translations/variants, not on
        // the model itself). Every one of those fields therefore resolves
        // to null on the actual Product model returned by
        // SearchRepository::searchProducts(). This assertion documents that
        // real, current behavior — it is very likely a bug worth the
        // team's attention (see the final report) rather than a sign this
        // test is wrong.
        $response->assertJsonPath('data.0.product_name', null)
            ->assertJsonPath('data.0.slug', null)
            ->assertJsonPath('data.0.price', null);
    }
}
