<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cart controller HTTP tests.
 *
 * Covers: /api/v1/storefront/stores/{store}/cart (+ /items, /bulk, /clear).
 * All requests go through the real routes, real CartController, real
 * Cart/CartItem actions and repositories, and a real product/variant chain.
 */
class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $this->customer = User::factory()->customer()->verified()->create();
    }

    private function actingAsCustomer(User $user): static
    {
        Sanctum::actingAs($user, ['*'], 'customer');

        return $this;
    }

    private function createVariant(int $quantity = 10, float $price = 25.00, bool $isActive = true): ProductVariant
    {
        $category = Category::query()->create([
            'name' => 'Test Category',
            'slug' => 'test-category-' . uniqid(),
            'parent_id' => null,
        ]);

        $brand = Brand::query()->create([
            'name' => 'Test Brand',
            'slug' => 'test-brand-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);

        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-' . uniqid(),
            'price' => $price,
            'quantity' => $quantity,
            'is_active' => $isActive,
        ]);
    }

    private function cartUrl(string $suffix = ''): string
    {
        return "/api/v1/storefront/stores/{$this->store->id}/cart{$suffix}";
    }

    // ── Auth ─────────────────────────────────────────────────────

    public function test_guest_cannot_view_cart(): void
    {
        $this->getJson($this->cartUrl())->assertUnauthorized();
    }

    public function test_guest_cannot_add_item_to_cart(): void
    {
        $variant = $this->createVariant();

        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertUnauthorized();
    }

    // ── Happy path ───────────────────────────────────────────────

    public function test_authenticated_customer_sees_empty_cart_by_default(): void
    {
        $response = $this->actingAsCustomer($this->customer)
            ->getJson($this->cartUrl());

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, Cart::where('user_id', $this->customer->id)->first()?->items()->count() ?? 0);
    }

    public function test_customer_can_add_item_to_cart(): void
    {
        $variant = $this->createVariant(quantity: 10, price: 25.00);

        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->cartUrl('/items'), [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_same_variant_twice_increments_quantity_instead_of_duplicating(): void
    {
        $variant = $this->createVariant(quantity: 10);
        $this->actingAsCustomer($this->customer);

        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk();

        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertOk();

        $this->assertSame(1, CartItem::where('product_variant_id', $variant->id)->count());
        $this->assertSame(5, CartItem::where('product_variant_id', $variant->id)->first()->quantity);
    }

    public function test_customer_can_update_cart_item_quantity(): void
    {
        $variant = $this->createVariant(quantity: 10);
        $this->actingAsCustomer($this->customer);

        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $item = CartItem::where('product_variant_id', $variant->id)->firstOrFail();

        $response = $this->patchJson($this->cartUrl("/items/{$item->id}"), [
            'quantity' => 4,
        ]);

        $response->assertOk();
        $this->assertSame(4, $item->fresh()->quantity);
    }

    public function test_customer_can_remove_item_from_cart(): void
    {
        $variant = $this->createVariant(quantity: 10);
        $this->actingAsCustomer($this->customer);

        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $item = CartItem::where('product_variant_id', $variant->id)->firstOrFail();

        $this->deleteJson($this->cartUrl("/items/{$item->id}"))->assertOk();

        $this->assertSoftDeleted('cart_items', ['id' => $item->id]);
    }

    public function test_customer_can_clear_cart(): void
    {
        $variantOne = $this->createVariant(quantity: 10);
        $variantTwo = $this->createVariant(quantity: 10);
        $this->actingAsCustomer($this->customer);

        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variantOne->id,
            'quantity' => 1,
        ])->assertOk();
        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variantTwo->id,
            'quantity' => 1,
        ])->assertOk();

        $this->deleteJson($this->cartUrl('/clear'))->assertOk();

        $cart = Cart::where('user_id', $this->customer->id)->where('store_id', $this->store->id)->first();
        $this->assertSame(0, $cart->items()->count());
    }

    public function test_bulk_add_skips_inactive_variants(): void
    {
        $activeVariant = $this->createVariant(quantity: 10, isActive: true);
        $inactiveVariant = $this->createVariant(quantity: 10, isActive: false);

        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->cartUrl('/bulk'), [
                'items' => [
                    ['product_variant_id' => $activeVariant->id, 'quantity' => 2],
                    ['product_variant_id' => $inactiveVariant->id, 'quantity' => 2],
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $activeVariant->id]);
        $this->assertDatabaseMissing('cart_items', ['product_variant_id' => $inactiveVariant->id]);
    }

    // ── Validation (422) ─────────────────────────────────────────

    public function test_add_item_requires_product_variant_id(): void
    {
        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->cartUrl('/items'), [
                'quantity' => 1,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['product_variant_id']);
    }

    public function test_add_item_rejects_nonexistent_variant(): void
    {
        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->cartUrl('/items'), [
                'product_variant_id' => 999999,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['product_variant_id']);
    }

    public function test_add_item_rejects_quantity_above_max_of_ten(): void
    {
        $variant = $this->createVariant(quantity: 50);

        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->cartUrl('/items'), [
                'product_variant_id' => $variant->id,
                'quantity' => 11,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }

    public function test_update_item_rejects_zero_quantity(): void
    {
        $variant = $this->createVariant(quantity: 10);
        $this->actingAsCustomer($this->customer);

        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();
        $item = CartItem::where('product_variant_id', $variant->id)->firstOrFail();

        $response = $this->patchJson($this->cartUrl("/items/{$item->id}"), [
            'quantity' => 0,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }

    // ── Edge cases: stock ────────────────────────────────────────

    public function test_add_item_fails_when_requested_quantity_exceeds_stock(): void
    {
        $variant = $this->createVariant(quantity: 2);

        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->cartUrl('/items'), [
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ]);

        // OutOfStockException maps to 400, not 422 — this is real app behavior,
        // not a validation error, even though it looks similar from the client.
        $response->assertStatus(400)->assertJsonPath('success', false);
        $this->assertDatabaseMissing('cart_items', ['product_variant_id' => $variant->id]);
    }

    public function test_update_item_fails_when_new_quantity_exceeds_stock(): void
    {
        $variant = $this->createVariant(quantity: 3);
        $this->actingAsCustomer($this->customer);

        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk();
        $item = CartItem::where('product_variant_id', $variant->id)->firstOrFail();

        $response = $this->patchJson($this->cartUrl("/items/{$item->id}"), [
            'quantity' => 500,
        ]);

        // UpdateItemRequest only caps quantity at 1000, so this reaches the
        // action, which must reject it against real stock (400).
        $response->assertStatus(400);
        $this->assertSame(2, $item->fresh()->quantity);
    }

    // ── Store isolation (this one IS correctly enforced) ──────────

    public function test_removing_item_belonging_to_a_different_store_is_rejected(): void
    {
        $variant = $this->createVariant(quantity: 10);
        $this->actingAsCustomer($this->customer);

        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();
        $item = CartItem::where('product_variant_id', $variant->id)->firstOrFail();

        $otherStore = Store::factory()->create();

        $response = $this->deleteJson(
            "/api/v1/storefront/stores/{$otherStore->id}/cart/items/{$item->id}"
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    // ── KNOWN BUG: cross-user cart item isolation ─────────────────
    //
    // RemoveCartItemAction / UpdateCartItemAction only check that the item's
    // cart belongs to the requesting store (`$item->cart->store_id`). They
    // never check that the item's cart belongs to the requesting *user*.
    // CartItemRepository::findById() is a global, unscoped lookup by id.
    // As written, any authenticated customer of a store can delete or
    // mutate another customer's cart item in that same store simply by
    // guessing/incrementing the item id (IDOR).
    //
    // These two tests assert the SECURE behavior (403 / item untouched).
    // They are expected to FAIL against the current code — that failure is
    // the point: it is a reproducible confirmation of the vulnerability,
    // not a mistake in the test. See final report.

    public function test_customer_cannot_remove_another_customers_cart_item(): void
    {
        $variant = $this->createVariant(quantity: 10);

        $victim = User::factory()->customer()->verified()->create();
        Sanctum::actingAs($victim, ['*'], 'customer');
        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();
        $victimItem = CartItem::where('product_variant_id', $variant->id)->firstOrFail();

        $attacker = User::factory()->customer()->verified()->create();
        $response = $this->actingAsCustomer($attacker)
            ->deleteJson($this->cartUrl("/items/{$victimItem->id}"));

        $response->assertStatus(403);
        $this->assertDatabaseHas('cart_items', ['id' => $victimItem->id, 'deleted_at' => null]);
    }

    public function test_customer_cannot_update_another_customers_cart_item(): void
    {
        $variant = $this->createVariant(quantity: 10);

        $victim = User::factory()->customer()->verified()->create();
        Sanctum::actingAs($victim, ['*'], 'customer');
        $this->postJson($this->cartUrl('/items'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();
        $victimItem = CartItem::where('product_variant_id', $variant->id)->firstOrFail();

        $attacker = User::factory()->customer()->verified()->create();
        $response = $this->actingAsCustomer($attacker)
            ->patchJson($this->cartUrl("/items/{$victimItem->id}"), ['quantity' => 9]);

        $response->assertStatus(403);
        $this->assertSame(1, $victimItem->fresh()->quantity);
    }
}
