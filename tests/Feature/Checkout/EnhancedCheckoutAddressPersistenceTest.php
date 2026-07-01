<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Enums\Address\AddressTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\EnhancedCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Tests\TestCase;

class EnhancedCheckoutAddressPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_checkout_persists_same_shipping_and_billing_address_as_both_defaults(): void
    {
        [$store, $user, $variant] = $this->createCheckoutFixture();

        $previousShipping = Address::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => AddressTypeEnum::SHIPPING,
            'name' => 'Previous Shipping',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '100 Old Shipping St',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
            'phone' => '+1-555-0001',
            'is_default_shipping' => true,
            'is_default_billing' => false,
        ]);

        $previousBilling = Address::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => AddressTypeEnum::BILLING,
            'name' => 'Previous Billing',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '200 Old Billing Ave',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78702',
            'country' => 'US',
            'phone' => '+1-555-0002',
            'is_default_shipping' => false,
            'is_default_billing' => true,
        ]);

        $order = $this->createPendingOrder($store, $user, $variant, 'pi_test_both_defaults');

        cache()->put("checkout_session:{$order->id}", [
            'shipping_address' => $this->checkoutAddressPayload(
                'Jane',
                'Doe',
                '500 Unified Address Rd',
                'Austin',
                'TX',
                '78703',
                'US',
                '+1-555-0100'
            ),
            'billing_address' => $this->checkoutAddressPayload(
                'Jane',
                'Doe',
                '500 Unified Address Rd',
                'Austin',
                'TX',
                '78703',
                'US',
                '+1-555-0100'
            ),
            'shipping_method_id' => 1,
        ]);

        $this->bindSuccessfulPaymentIntent('pi_test_both_defaults');

        $order = $this->app->make(EnhancedCheckoutService::class)->completeCheckout('pi_test_both_defaults');

        $this->assertNotNull($order->shipping_address_id);
        $this->assertSame($order->shipping_address_id, $order->billing_address_id);

        $savedAddress = Address::query()->findOrFail($order->shipping_address_id);
        $this->assertSame(AddressTypeEnum::BOTH, $savedAddress->type);
        $this->assertTrue($savedAddress->is_default_shipping);
        $this->assertTrue($savedAddress->is_default_billing);
        $this->assertSame($store->id, $savedAddress->store_id);
        $this->assertSame($user->id, $savedAddress->user_id);

        $previousShipping->refresh();
        $previousBilling->refresh();

        $this->assertFalse($previousShipping->is_default_shipping);
        $this->assertFalse($previousBilling->is_default_billing);
    }

    public function test_complete_checkout_reuses_matching_store_address_and_upgrades_it_to_both_defaults(): void
    {
        [$store, $user, $variant] = $this->createCheckoutFixture();

        $existingAddress = Address::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => AddressTypeEnum::SHIPPING,
            'name' => 'Jane Doe',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '500 Unified Address Rd',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78703',
            'country' => 'US',
            'phone' => null,
            'email' => null,
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);

        $order = $this->createPendingOrder($store, $user, $variant, 'pi_test_existing_address');

        cache()->put("checkout_session:{$order->id}", [
            'shipping_address' => $this->checkoutAddressPayload(
                'Jane',
                'Doe',
                '500 Unified Address Rd',
                'Austin',
                'TX',
                '78703',
                'US',
                '+1-555-0100',
                'jane@example.com'
            ),
            'billing_address' => $this->checkoutAddressPayload(
                'Jane',
                'Doe',
                '500 Unified Address Rd',
                'Austin',
                'TX',
                '78703',
                'US',
                '+1-555-0100',
                'jane@example.com'
            ),
            'shipping_method_id' => 1,
        ]);

        $this->bindSuccessfulPaymentIntent('pi_test_existing_address');

        $order = $this->app->make(EnhancedCheckoutService::class)->completeCheckout('pi_test_existing_address');

        $existingAddress->refresh();

        $this->assertSame($existingAddress->id, $order->shipping_address_id);
        $this->assertSame($existingAddress->id, $order->billing_address_id);
        $this->assertSame(AddressTypeEnum::BOTH, $existingAddress->type);
        $this->assertTrue($existingAddress->is_default_shipping);
        $this->assertTrue($existingAddress->is_default_billing);
        $this->assertSame('+1-555-0100', $existingAddress->phone);
        $this->assertSame('jane@example.com', $existingAddress->email);

        $this->assertSame(1, Address::query()
            ->where('user_id', $user->id)
            ->where('store_id', $store->id)
            ->count());
    }

    private function createCheckoutFixture(): array
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $user = User::factory()->customer()->verified()->create();

        $category = Category::query()->create([
            'store_id' => $store->id,
            'slug' => 'enhanced-checkout-addresses',
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
            'sku' => 'SKU-ENHANCED-ADDR',
            'price' => 29.99,
            'quantity' => 10,
            'is_active' => true,
        ]);

        return [$store, $user, $variant];
    }

    private function createPendingOrder(
        Store $store,
        User $user,
        ProductVariant $variant,
        string $paymentIntentId
    ): Order {
        $order = Order::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'subtotal' => 29.99,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total' => 29.99,
            'currency' => 'usd',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
            'payment_intent_id' => $paymentIntentId,
            'shipping_method' => 'Standard',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Enhanced Checkout Product',
            'sku' => $variant->sku,
            'unit_price' => 29.99,
            'unit_discount_percentage' => 0,
            'quantity' => 1,
            'subtotal' => 29.99,
            'total' => 29.99,
        ]);

        return $order;
    }

    private function checkoutAddressPayload(
        string $firstName,
        string $lastName,
        string $addressLine1,
        string $city,
        string $state,
        string $postalCode,
        string $country,
        ?string $phone = null,
        ?string $email = null
    ): array {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address_line_1' => $addressLine1,
            'address_line_2' => null,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postalCode,
            'country' => $country,
            'phone' => $phone,
            'email' => $email,
        ];
    }

    private function bindSuccessfulPaymentIntent(string $paymentIntentId): void
    {
        $stripeClient = new class($paymentIntentId) extends StripeClient {
            public object $paymentIntents;

            public function __construct(string $paymentIntentId)
            {
                $this->paymentIntents = new class($paymentIntentId) {
                    public function __construct(private string $paymentIntentId) {}

                    public function retrieve(string $paymentIntentId): PaymentIntent
                    {
                        $paymentIntent = new PaymentIntent($this->paymentIntentId);
                        $paymentIntent->status = 'succeeded';

                        return $paymentIntent;
                    }
                };
            }
        };

        $this->app->instance(StripeClient::class, $stripeClient);
    }
}
