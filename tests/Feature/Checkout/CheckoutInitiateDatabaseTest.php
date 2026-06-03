<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Stripe\Checkout\Session;
use Tests\TestCase;

class CheckoutInitiateDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * Create a minimal active store with one product variant.
     * @return array{0: Store, 1: Product, 2: ProductVariant}
     */
    private function createStoreWithVariant(): array
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'store_id' => $store->id,
            'slug' => 'db-test-category',
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'brand_id' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-DB-TEST',
            'price' => 49.99,
            'quantity' => 10,
            'is_active' => true,
        ]);

        return [$store, $product, $variant];
    }

    public function test_guest_checkout_creates_order_with_null_user_id_and_populated_guest_email(): void
    {
        [$store, , $variant] = $this->createStoreWithVariant();

        $this->swapCheckoutService();

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", [
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 2],
            ],
            'email' => 'guest@example.com',
        ]);

        $response->assertOk();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertSame('guest@example.com', $order->guest_email);
        $this->assertSame(OrderStatusEnum::PENDING, $order->status);
        $this->assertSame(PaymentStatusEnum::PENDING, $order->payment_status);
        $this->assertSame($store->id, $order->store_id);
        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('ORD-', $order->order_number);
        $this->assertNotNull($order->stripe_checkout_session_id);
    }

    public function test_guest_checkout_creates_order_items_with_correct_values(): void
    {
        [$store, , $variant] = $this->createStoreWithVariant();

        $this->swapCheckoutService();

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", [
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 3],
            ],
        ]);

        $response->assertOk();

        $order = Order::first();
        $this->assertNotNull($order);

        $items = OrderItem::all();
        $this->assertCount(1, $items);

        $item = $items->first();
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertEqualsWithDelta(49.99, (float) $item->unit_price, 0.001);
        $this->assertSame(3, $item->quantity);
        $this->assertEqualsWithDelta(49.99 * 3, (float) $item->subtotal, 0.001);
        $this->assertEqualsWithDelta(49.99 * 3, (float) $item->total, 0.001);
        $this->assertSame($variant->sku, $item->sku);
    }

    public function test_guest_checkout_calculates_order_totals_correctly(): void
    {
        [$store, , $variant] = $this->createStoreWithVariant();

        $this->swapCheckoutService();

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", [
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 5],
            ],
        ]);

        $response->assertOk();

        $order = Order::first();
        $this->assertNotNull($order);

        $expectedSubtotal = 49.99 * 5;
        $this->assertEqualsWithDelta($expectedSubtotal, (float) $order->subtotal, 0.001);
        $this->assertSame(0.0, (float) $order->tax_amount);
        $this->assertSame(0.0, (float) $order->shipping_amount);
        $this->assertSame(0.0, (float) $order->discount_amount);
        $this->assertEqualsWithDelta($expectedSubtotal, (float) $order->total, 0.001);
        $this->assertSame('usd', $order->currency);
    }

    public function test_authenticated_checkout_creates_order_with_user_id(): void
    {
        [$store, , $variant] = $this->createStoreWithVariant();

        $user = User::factory()->customer()->verified()->create();

        $cart = Cart::query()->create([
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 4,
            'unit_price' => 49.99,
        ]);

        Sanctum::actingAs($user, ['*'], 'customer');

        $this->swapCheckoutService();

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout");

        $response->assertOk();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame($user->id, $order->user_id);
        $this->assertNull($order->guest_email);
    }

    public function test_authenticated_customer_can_list_paid_orders_after_checkout(): void
    {
        [$store, $product, $variant] = $this->createStoreWithVariant();

        $user = User::factory()->customer()->verified()->create();

        $order = Order::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'subtotal' => 99.98,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total' => 99.98,
            'currency' => 'usd',
            'status' => OrderStatusEnum::PROCESSING,
            'payment_status' => PaymentStatusEnum::PAID,
            'shipping_method' => 'free',
            'stripe_checkout_session_id' => 'cs_test_list_orders',
            'payment_intent_id' => 'pi_test_list_orders',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Serialized Test Product',
            'sku' => $variant->sku,
            'unit_price' => 49.99,
            'unit_discount_percentage' => 0,
            'quantity' => 2,
            'subtotal' => 99.98,
            'total' => 99.98,
            'attributes' => [],
        ]);

        Sanctum::actingAs($user, ['*'], 'customer');

        $this->getJson("/api/v1/storefront/stores/{$store->id}/orders")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', $order->order_number)
            ->assertJsonPath('data.0.payment_status', PaymentStatusEnum::PAID->value)
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_checkout_raises_error_for_insufficient_stock(): void
    {
        [$store, , $variant] = $this->createStoreWithVariant();

        $this->swapCheckoutService();

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", [
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 999],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount(Order::class, 0);
        $this->assertDatabaseCount(OrderItem::class, 0);
    }

    public function test_checkout_raises_error_for_inactive_variant(): void
    {
        [$store, , $variant] = $this->createStoreWithVariant();

        $variant->update(['is_active' => false]);

        $this->swapCheckoutService();

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", [
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 1],
            ],
        ]);

        // OutOfStockException defaults to 400 status
        $response->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount(Order::class, 0);
    }

    public function test_guest_checkout_rejects_cross_store_variant(): void
    {
        [$storeA, , $variantA] = $this->createStoreWithVariant();

        // Create a second store with its own variant
        $storeB = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $categoryB = Category::query()->create([
            'store_id' => $storeB->id,
            'slug' => 'cross-store-test-category',
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $productB = Product::query()->create([
            'store_id' => $storeB->id,
            'category_id' => $categoryB->id,
            'brand_id' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);

        $variantB = ProductVariant::query()->create([
            'product_id' => $productB->id,
            'sku' => 'SKU-CROSS-STORE',
            'price' => 19.99,
            'quantity' => 10,
            'is_active' => true,
        ]);

        $this->swapCheckoutService();

        // Attempt checkout for Store A with Store B's variant
        $response = $this->postJson("/api/v1/storefront/stores/{$storeA->id}/checkout", [
            'items' => [
                ['product_variant_id' => $variantB->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_checkout_rejects_cross_store_variant(): void
    {
        [$storeA, , $variantA] = $this->createStoreWithVariant();

        // Create a second store with its own variant
        $storeB = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $categoryB = Category::query()->create([
            'store_id' => $storeB->id,
            'slug' => 'auth-cross-store-category',
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $productB = Product::query()->create([
            'store_id' => $storeB->id,
            'category_id' => $categoryB->id,
            'brand_id' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);

        $variantB = ProductVariant::query()->create([
            'product_id' => $productB->id,
            'sku' => 'SKU-AUTH-CROSS',
            'price' => 29.99,
            'quantity' => 5,
            'is_active' => true,
        ]);

        $user = User::factory()->customer()->verified()->create();

        // Create a cart for Store A containing Store B's variant
        $cart = Cart::query()->create([
            'user_id' => $user->id,
            'store_id' => $storeA->id,
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variantB->id,
            'quantity' => 2,
            'unit_price' => 29.99,
        ]);

        Sanctum::actingAs($user, ['*'], 'customer');

        $this->swapCheckoutService();

        $response = $this->postJson("/api/v1/storefront/stores/{$storeA->id}/checkout");

        $response->assertStatus(422);
    }

    /**
     * Swap the CheckoutService in the container with one that fakes Stripe.
     */
    private function swapCheckoutService(): void
    {
        $this->app->singleton(CheckoutService::class, function (): CheckoutService {
            return new class extends CheckoutService
            {
                protected function createStripeSession(array $sessionParams): Session
                {
                    $session = new Session('cs_test_fake_' . bin2hex(random_bytes(8)));
                    $session->url = 'https://checkout.stripe.com/' . $session->id;
                    return $session;
                }
            };
        });
    }
}
