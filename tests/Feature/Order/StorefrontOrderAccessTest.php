<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers /api/v1/storefront/stores/{store}/orders (index/show/cancel)
 * through the real OrderController + OrderPolicy.
 *
 * Unlike Cart and Asset, order ownership isolation IS correctly enforced
 * here: GetOrderAction fetches by order_number + store only (no user
 * scoping), but the controller explicitly calls $this->authorize('view'
 * / 'cancel', $order) against the real OrderPolicy before doing anything
 * else, and OrderPolicy checks $user->id === $order->user_id for the
 * customer branch. These tests confirm that.
 */
class StorefrontOrderAccessTest extends TestCase
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

    private function createOrderForUser(User $user, array $overrides = []): Order
    {
        $order = Order::factory()->for($this->store)->for($user)->create(array_merge([
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
        ], $overrides));

        $variant = ProductVariant::query()->create([
            'product_id' => $this->makeProductId(),
            'sku' => 'SKU-ORD-' . uniqid(),
            'price' => 20.00,
            'quantity' => 3,
            'is_active' => true,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Test Product',
            'sku' => $variant->sku,
            'unit_price' => 20.00,
            'unit_discount_percentage' => 0,
            'quantity' => 2,
            'subtotal' => 40.00,
            'total' => 40.00,
        ]);

        return $order->fresh();
    }

    private function makeProductId(): int
    {
        $category = \App\Models\Category::query()->create([
            'name' => 'Order Test Category',
            'slug' => 'order-test-category-' . uniqid(),
            'parent_id' => null,
        ]);
        $brand = \App\Models\Brand::query()->create([
            'name' => 'Order Test Brand',
            'slug' => 'order-test-brand-' . uniqid(),
            'is_active' => true,
        ]);

        return \App\Models\Product::query()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ])->id;
    }

    private function showUrl(Order $order): string
    {
        return "/api/v1/storefront/stores/{$this->store->id}/orders/{$order->order_number}";
    }

    private function cancelUrl(Order $order): string
    {
        return "/api/v1/storefront/stores/{$this->store->id}/orders/{$order->order_number}/cancel";
    }

    // ── Auth ─────────────────────────────────────────────────────

    public function test_guest_cannot_view_orders(): void
    {
        $order = $this->createOrderForUser($this->customer);

        $this->getJson($this->showUrl($order))->assertUnauthorized();
    }

    // ── Happy path ───────────────────────────────────────────────

    public function test_customer_can_view_their_own_order(): void
    {
        $order = $this->createOrderForUser($this->customer);

        $response = $this->actingAsCustomer($this->customer)->getJson($this->showUrl($order));

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_customer_can_cancel_their_own_pending_order(): void
    {
        $order = $this->createOrderForUser($this->customer);
        $variant = $order->items->first()->productVariant;
        $quantityBefore = $variant->quantity;

        $response = $this->actingAsCustomer($this->customer)->postJson($this->cancelUrl($order));

        $response->assertOk();
        $order->refresh();
        $this->assertSame(OrderStatusEnum::CANCELLED, $order->status);

        // Stock is restored on cancellation.
        $this->assertSame($quantityBefore + 2, $variant->fresh()->quantity);
    }

    public function test_customer_can_list_only_their_own_orders(): void
    {
        $this->createOrderForUser($this->customer);
        $otherCustomer = User::factory()->customer()->verified()->create();
        $this->createOrderForUser($otherCustomer);

        $response = $this->actingAsCustomer($this->customer)
            ->getJson("/api/v1/storefront/stores/{$this->store->id}/orders");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    // ── Cross-user isolation (403 — correctly enforced) ────────────

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $victim = User::factory()->customer()->verified()->create();
        $order = $this->createOrderForUser($victim);

        $response = $this->actingAsCustomer($this->customer)->getJson($this->showUrl($order));

        $response->assertStatus(403);
    }

    public function test_customer_cannot_cancel_another_customers_order(): void
    {
        $victim = User::factory()->customer()->verified()->create();
        $order = $this->createOrderForUser($victim);

        $response = $this->actingAsCustomer($this->customer)->postJson($this->cancelUrl($order));

        $response->assertStatus(403);
        $this->assertSame(OrderStatusEnum::PENDING, $order->fresh()->status);
    }

    // ── Edge cases ───────────────────────────────────────────────

    public function test_customer_cannot_cancel_an_already_shipped_order(): void
    {
        $order = $this->createOrderForUser($this->customer, [
            'status' => OrderStatusEnum::SHIPPED,
            'payment_status' => PaymentStatusEnum::PAID,
        ]);

        $response = $this->actingAsCustomer($this->customer)->postJson($this->cancelUrl($order));

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame(OrderStatusEnum::SHIPPED, $order->fresh()->status);
    }

    /**
     * Documents real behavior: OrderRepository::cancel() unconditionally
     * sets payment_status to REFUNDED, even for an order that was PENDING
     * and never actually paid or charged.
     */
    public function test_cancelling_an_unpaid_pending_order_still_sets_payment_status_to_refunded(): void
    {
        $order = $this->createOrderForUser($this->customer, [
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
        ]);

        $this->actingAsCustomer($this->customer)->postJson($this->cancelUrl($order))->assertOk();

        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->fresh()->payment_status);
    }

    public function test_viewing_a_nonexistent_order_number_returns_not_found(): void
    {
        $response = $this->actingAsCustomer($this->customer)
            ->getJson("/api/v1/storefront/stores/{$this->store->id}/orders/ORD-DOESNOTEXIST");

        $response->assertStatus(404);
    }
}
