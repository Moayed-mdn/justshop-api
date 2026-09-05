<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * Covers the real cart -> order -> payment flow through
 * EnhancedCheckoutService::createPaymentIntent() and ::completeCheckout(),
 * driven through the real HTTP routes:
 *   POST /stores/{store}/checkout/payment-intent
 *   POST /stores/{store}/checkout/complete
 *
 * This is the flow actually reachable from the app. App\Actions\Order\
 * CreateOrderAction is NOT used by any route (verified: no controller or
 * service references it) and is not exercised here — see final report.
 *
 * The webhook-driven path (StripeEcommerceWebhookController) already has
 * full coverage in tests/Feature/Checkout/StripeConnectEcommerceWebhookTest.php
 * and is out of scope here.
 */
class EnhancedCheckoutCartToOrderTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $customer;
    private ProductVariant $variant;
    private ShippingMethod $shippingMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->store = Store::factory()->create([
            'stripe_account_id' => 'acct_test_merchant',
            'stripe_charges_enabled' => true,
        ]);

        $this->customer = User::factory()->customer()->verified()->create();

        $category = Category::query()->create([
            'name' => 'Checkout Category',
            'slug' => 'checkout-category-' . uniqid(),
            'parent_id' => null,
        ]);
        $brand = Brand::query()->create([
            'name' => 'Checkout Brand',
            'slug' => 'checkout-brand-' . uniqid(),
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
        $this->variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-CHECKOUT-' . uniqid(),
            'price' => 40.00,
            'quantity' => 5,
            'is_active' => true,
        ]);

        $this->shippingMethod = ShippingMethod::query()->create([
            'store_id' => $this->store->id,
            'name' => 'Standard Shipping',
            'code' => 'standard',
            'price' => 10.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    private function actingAsCustomer(): static
    {
        Sanctum::actingAs($this->customer, ['*'], 'customer');

        return $this;
    }

    private function addItemToCart(int $quantity = 1): void
    {
        $cart = Cart::query()->create([
            'store_id' => $this->store->id,
            'user_id' => $this->customer->id,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $cart->items()->create([
            'store_id' => $this->store->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => $quantity,
            'unit_price' => $this->variant->price,
        ]);
    }

    private function addressPayload(): array
    {
        return [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
            'phone' => '+1-555-0100',
        ];
    }

    private function paymentIntentUrl(): string
    {
        return "/api/v1/storefront/stores/{$this->store->id}/checkout/payment-intent";
    }

    private function completeUrl(): string
    {
        return "/api/v1/storefront/stores/{$this->store->id}/checkout/complete";
    }

    /**
     * Binds a fake StripeClient whose paymentIntents->create() returns a
     * fresh unconfirmed intent and whose ->retrieve() reports whatever
     * status is requested. Mirrors the pattern already used in
     * EnhancedCheckoutAddressPersistenceTest.
     */
    private function bindStripeClient(string $paymentIntentId, string $retrieveStatus = 'succeeded'): void
    {
        $stripeClient = new class($paymentIntentId, $retrieveStatus) extends StripeClient {
            public object $paymentIntents;

            public function __construct(string $paymentIntentId, string $retrieveStatus)
            {
                $this->paymentIntents = new class($paymentIntentId, $retrieveStatus) {
                    public function __construct(
                        private string $paymentIntentId,
                        private string $retrieveStatus,
                    ) {}

                    public function create(array $params): PaymentIntent
                    {
                        $paymentIntent = new PaymentIntent($this->paymentIntentId);
                        $paymentIntent->client_secret = $this->paymentIntentId . '_secret';
                        $paymentIntent->status = 'requires_payment_method';

                        return $paymentIntent;
                    }

                    public function retrieve(string $paymentIntentId): PaymentIntent
                    {
                        $paymentIntent = new PaymentIntent($paymentIntentId);
                        $paymentIntent->status = $this->retrieveStatus;

                        return $paymentIntent;
                    }
                };
            }
        };

        $this->app->instance(StripeClient::class, $stripeClient);
    }

    // ── Auth ─────────────────────────────────────────────────────

    public function test_guest_cannot_create_payment_intent(): void
    {
        $this->addItemToCart();

        $response = $this->postJson($this->paymentIntentUrl(), [
            'shipping_address' => $this->addressPayload(),
            'billing_address' => $this->addressPayload(),
            'shipping_method_id' => $this->shippingMethod->id,
        ]);

        $response->assertUnauthorized();
    }

    // ── Happy path: cart -> order (createPaymentIntent) ────────────

    public function test_creating_a_payment_intent_builds_an_order_from_the_cart(): void
    {
        $this->addItemToCart(quantity: 2);
        $this->bindStripeClient('pi_test_create_order');

        $response = $this->actingAsCustomer()->postJson($this->paymentIntentUrl(), [
            'shipping_address' => $this->addressPayload(),
            'billing_address' => $this->addressPayload(),
            'shipping_method_id' => $this->shippingMethod->id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $order = Order::where('user_id', $this->customer->id)->firstOrFail();
        $this->assertSame(OrderStatusEnum::PENDING, $order->status);
        $this->assertSame(PaymentStatusEnum::PENDING, $order->payment_status);
        $this->assertSame(80.00, (float) $order->subtotal); // 2 x 40.00
        $this->assertSame(10.00, (float) $order->shipping_amount);
        $this->assertSame(90.00, (float) $order->total);
        $this->assertSame(1, $order->items()->count());

        // Stock is not touched at this stage — only on completion.
        $this->assertSame(5, $this->variant->fresh()->quantity);
    }

    public function test_creating_a_payment_intent_fails_when_cart_is_empty(): void
    {
        $this->bindStripeClient('pi_test_empty_cart');

        $response = $this->actingAsCustomer()->postJson($this->paymentIntentUrl(), [
            'shipping_address' => $this->addressPayload(),
            'billing_address' => $this->addressPayload(),
            'shipping_method_id' => $this->shippingMethod->id,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    // ── Validation (422) ─────────────────────────────────────────

    public function test_creating_a_payment_intent_requires_a_shipping_method_id(): void
    {
        $this->addItemToCart();

        $response = $this->actingAsCustomer()->postJson($this->paymentIntentUrl(), [
            'shipping_address' => $this->addressPayload(),
            'billing_address' => $this->addressPayload(),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['shipping_method_id']);
    }

    public function test_creating_a_payment_intent_rejects_a_shipping_method_from_another_store(): void
    {
        $this->addItemToCart();
        $otherStoreMethod = ShippingMethod::query()->create([
            'store_id' => Store::factory()->create()->id,
            'name' => 'Other Store Shipping',
            'code' => 'other',
            'price' => 5.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomer()->postJson($this->paymentIntentUrl(), [
            'shipping_address' => $this->addressPayload(),
            'billing_address' => $this->addressPayload(),
            'shipping_method_id' => $otherStoreMethod->id,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    // ── Edge case: store not payment-enabled ───────────────────────

    public function test_creating_a_payment_intent_fails_when_store_cannot_receive_payments(): void
    {
        $unonboardedStore = Store::factory()->create([
            'stripe_account_id' => null,
            'stripe_charges_enabled' => false,
        ]);
        $shippingMethod = ShippingMethod::query()->create([
            'store_id' => $unonboardedStore->id,
            'name' => 'Standard',
            'code' => 'standard',
            'price' => 5.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);
        Cart::query()->create([
            'store_id' => $unonboardedStore->id,
            'user_id' => $this->customer->id,
            'currency' => 'USD',
            'is_active' => true,
        ])->items()->create([
            'store_id' => $unonboardedStore->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
            'unit_price' => $this->variant->price,
        ]);

        $response = $this->actingAsCustomer()->postJson(
            "/api/v1/storefront/stores/{$unonboardedStore->id}/checkout/payment-intent",
            [
                'shipping_address' => $this->addressPayload(),
                'billing_address' => $this->addressPayload(),
                'shipping_method_id' => $shippingMethod->id,
            ]
        );

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    // ── Happy path: payment completion ──────────────────────────────

    public function test_completing_checkout_marks_order_paid_deducts_stock_and_clears_cart(): void
    {
        $this->addItemToCart(quantity: 2);
        $this->bindStripeClient('pi_test_complete');

        $this->actingAsCustomer()->postJson($this->paymentIntentUrl(), [
            'shipping_address' => $this->addressPayload(),
            'billing_address' => $this->addressPayload(),
            'shipping_method_id' => $this->shippingMethod->id,
        ])->assertOk();

        $order = Order::where('user_id', $this->customer->id)->firstOrFail();
        $this->bindStripeClient($order->payment_intent_id, 'succeeded');

        $response = $this->postJson($this->completeUrl(), [
            'payment_intent_id' => $order->payment_intent_id,
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertSame(3, $this->variant->fresh()->quantity); // 5 - 2

        $cart = Cart::where('user_id', $this->customer->id)->where('store_id', $this->store->id)->first();
        $this->assertSame(0, $cart->items()->count());
    }

    public function test_completing_checkout_is_idempotent_when_order_is_already_paid(): void
    {
        $this->addItemToCart(quantity: 1);
        $this->bindStripeClient('pi_test_idempotent');

        $this->actingAsCustomer()->postJson($this->paymentIntentUrl(), [
            'shipping_address' => $this->addressPayload(),
            'billing_address' => $this->addressPayload(),
            'shipping_method_id' => $this->shippingMethod->id,
        ])->assertOk();

        $order = Order::where('user_id', $this->customer->id)->firstOrFail();
        $this->bindStripeClient($order->payment_intent_id, 'succeeded');

        $this->postJson($this->completeUrl(), ['payment_intent_id' => $order->payment_intent_id])->assertOk();
        $quantityAfterFirstComplete = $this->variant->fresh()->quantity;

        // Calling complete a second time for the same, already-paid order
        // must not deduct stock again.
        $this->postJson($this->completeUrl(), ['payment_intent_id' => $order->payment_intent_id])->assertOk();

        $this->assertSame($quantityAfterFirstComplete, $this->variant->fresh()->quantity);
    }

    // ── Edge case: payment not actually succeeded ────────────────

    public function test_completing_checkout_fails_when_payment_has_not_succeeded(): void
    {
        $this->addItemToCart(quantity: 1);
        $this->bindStripeClient('pi_test_not_succeeded');

        $this->actingAsCustomer()->postJson($this->paymentIntentUrl(), [
            'shipping_address' => $this->addressPayload(),
            'billing_address' => $this->addressPayload(),
            'shipping_method_id' => $this->shippingMethod->id,
        ])->assertOk();

        $order = Order::where('user_id', $this->customer->id)->firstOrFail();
        $this->bindStripeClient($order->payment_intent_id, 'requires_payment_method');

        $response = $this->postJson($this->completeUrl(), [
            'payment_intent_id' => $order->payment_intent_id,
        ]);

        $response->assertStatus(400);
        $this->assertSame(PaymentStatusEnum::PENDING, $order->fresh()->payment_status);
        $this->assertSame(5, $this->variant->fresh()->quantity);
    }

    /**
     * Edge case documenting real behavior: stock deduction on completion
     * uses max(0, $variant->quantity - $item->quantity) — it clamps to zero
     * rather than re-validating availability or failing. If stock drops
     * below the ordered quantity between order-creation and payment
     * completion (e.g. another order or a manual adjustment), completing
     * checkout silently oversells instead of rejecting the payment.
     */
    public function test_completing_checkout_clamps_stock_to_zero_instead_of_rejecting_oversell(): void
    {
        $this->addItemToCart(quantity: 2);
        $this->bindStripeClient('pi_test_oversell');

        $this->actingAsCustomer()->postJson($this->paymentIntentUrl(), [
            'shipping_address' => $this->addressPayload(),
            'billing_address' => $this->addressPayload(),
            'shipping_method_id' => $this->shippingMethod->id,
        ])->assertOk();

        // Simulate stock disappearing after the order was created but
        // before payment completed.
        $this->variant->update(['quantity' => 1]);

        $order = Order::where('user_id', $this->customer->id)->firstOrFail();
        $this->bindStripeClient($order->payment_intent_id, 'succeeded');

        $response = $this->postJson($this->completeUrl(), [
            'payment_intent_id' => $order->payment_intent_id,
        ]);

        $response->assertOk();
        $this->assertSame(0, $this->variant->fresh()->quantity);
        $this->assertSame(PaymentStatusEnum::PAID, $order->fresh()->payment_status);
    }
}
