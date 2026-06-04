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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session;
use Stripe\StripeObject;
use Tests\TestCase;

class CheckoutWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private ProductVariant $variant;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'store_id' => $store->id,
            'slug' => 'webhook-test-category',
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
            'sku' => 'SKU-WEBHOOK-TEST',
            'price' => 29.99,
            'quantity' => 10,
            'is_active' => true,
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'subtotal' => 59.98,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total' => 59.98,
            'currency' => 'usd',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
            'shipping_method' => 'free',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Test Product',
            'sku' => $variant->sku,
            'unit_price' => 29.99,
            'unit_discount_percentage' => 0,
            'quantity' => 2,
            'subtotal' => 59.98,
            'total' => 59.98,
        ]);

        $this->store = $store;
        $this->variant = $variant;
        $this->order = $order;
    }

    private function createStripeSessionWithOrder(Order $order): Session
    {
        $session = new Session('cs_test_' . bin2hex(random_bytes(8)));
        $session->payment_intent = 'pi_test_' . bin2hex(random_bytes(8));

        $metadata = new StripeObject();
        $metadata->order_id = (string) $order->id;
        $session->metadata = $metadata;

        $address = new StripeObject();
        $address->line1 = '123 Test St';
        $address->line2 = null;
        $address->city = 'Test City';
        $address->state = 'TS';
        $address->postal_code = '12345';
        $address->country = 'US';

        $customerDetails = new StripeObject();
        $customerDetails->email = 'customer@example.com';
        $customerDetails->name = 'Test Customer';
        $customerDetails->phone = '+1234567890';
        $customerDetails->address = $address;
        $session->customer_details = $customerDetails;

        $session->shipping_details = null;
        $session->payment_status = 'paid';
        return $session;
    }

    public function test_handle_checkout_completed_marks_order_as_paid(): void
    {
        $service = $this->app->make(CheckoutService::class);
        $session = $this->createStripeSessionWithOrder($this->order);

        $service->handleCheckoutCompleted($session);

        $this->order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID->value, $this->order->payment_status->value);
        $this->assertSame(OrderStatusEnum::PROCESSING->value, $this->order->status->value);
        $this->assertNotNull($this->order->payment_intent_id);
    }

    public function test_handle_checkout_completed_deducts_stock(): void
    {
        $service = $this->app->make(CheckoutService::class);
        $session = $this->createStripeSessionWithOrder($this->order);

        $service->handleCheckoutCompleted($session);

        $this->variant->refresh();
        $this->assertSame(8, $this->variant->quantity);
    }

    public function test_handle_checkout_completed_saves_shipping_address(): void
    {
        $service = $this->app->make(CheckoutService::class);
        $session = $this->createStripeSessionWithOrder($this->order);

        $service->handleCheckoutCompleted($session);

        $this->order->refresh();
        $this->assertNotNull($this->order->shipping_address_data);
        $this->assertSame('Test Customer', $this->order->shipping_address_data['name']);
        $this->assertSame('123 Test St', $this->order->shipping_address_data['address']['line1']);
        $this->assertSame('Test City', $this->order->shipping_address_data['address']['city']);
    }

    public function test_handle_checkout_completed_saves_guest_email(): void
    {
        $order = Order::create([
            'store_id' => $this->store->id,
            'subtotal' => 29.99,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total' => 29.99,
            'currency' => 'usd',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
            'shipping_method' => 'free',
        ]);

        $service = $this->app->make(CheckoutService::class);
        $session = $this->createStripeSessionWithOrder($order);

        $service->handleCheckoutCompleted($session);

        $order->refresh();
        $this->assertSame('customer@example.com', $order->guest_email);
    }

    public function test_handle_checkout_completed_clears_cart_for_logged_in_user(): void
    {
        $user = User::factory()->customer()->verified()->create();

        $order = Order::create([
            'store_id' => $this->store->id,
            'user_id' => $user->id,
            'subtotal' => 29.99,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total' => 29.99,
            'currency' => 'usd',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
            'shipping_method' => 'free',
        ]);

        $cart = Cart::query()->create([
            'user_id' => $user->id,
            'store_id' => $this->store->id,
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'unit_price' => 29.99,
        ]);

        $service = $this->app->make(CheckoutService::class);
        $session = $this->createStripeSessionWithOrder($order);

        $service->handleCheckoutCompleted($session);

        $this->assertDatabaseCount(CartItem::class, 0);
    }

    public function test_handle_checkout_completed_skips_double_processing(): void
    {
        $service = $this->app->make(CheckoutService::class);
        $session = $this->createStripeSessionWithOrder($this->order);

        $service->handleCheckoutCompleted($session);

        $this->order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID->value, $this->order->payment_status->value);
        $expectedIntent = $this->order->payment_intent_id;

        $anotherSession = $this->createStripeSessionWithOrder($this->order);
        $service->handleCheckoutCompleted($anotherSession);

        $this->order->refresh();
        $this->assertSame($expectedIntent, $this->order->payment_intent_id);
    }

    public function test_handle_checkout_completed_skips_when_order_id_missing(): void
    {
        $service = $this->app->make(CheckoutService::class);
        $session = new Session('cs_test_no_metadata');
        $session->metadata = new StripeObject([]);

        $service->handleCheckoutCompleted($session);

        $this->order->refresh();
        $this->assertSame(PaymentStatusEnum::PENDING, $this->order->payment_status);
    }

    public function test_handle_session_expired_marks_order_as_failed(): void
    {
        $service = $this->app->make(CheckoutService::class);
        $session = $this->createStripeSessionWithOrder($this->order);

        $service->handleSessionExpired($session);

        $this->order->refresh();
        $this->assertSame(PaymentStatusEnum::FAILED->value, $this->order->payment_status->value);
        $this->assertSame(OrderStatusEnum::CANCELLED->value, $this->order->status->value);
    }

    public function test_handle_session_expired_skips_already_paid_order(): void
    {
        $this->order->markAsPaid();
        $this->order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID->value, $this->order->payment_status->value);

        $service = $this->app->make(CheckoutService::class);
        $session = $this->createStripeSessionWithOrder($this->order);

        $service->handleSessionExpired($session);

        $this->order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID->value, $this->order->payment_status->value);
    }

    public function test_handle_session_expired_skips_when_order_id_missing(): void
    {
        $service = $this->app->make(CheckoutService::class);
        $session = new Session('cs_test_no_metadata');
        $session->metadata = new StripeObject([]);

        $service->handleSessionExpired($session);

        $this->order->refresh();
        $this->assertSame(PaymentStatusEnum::PENDING, $this->order->payment_status);
    }
}
