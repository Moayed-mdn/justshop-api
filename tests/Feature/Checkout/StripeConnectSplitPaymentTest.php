<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Exceptions\BaseApiException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\User;
use App\Services\EnhancedCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeConnectSplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $user;
    private Cart $cart;
    private ShippingMethod $shippingMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable Store and Order observers to avoid SQLite GREATEST function issue
        Store::unsetEventDispatcher();
        \App\Models\Order::unsetEventDispatcher();

        Config::set('services.stripe.platform_fee_percent', 3.0);
        Config::set('services.stripe.connect_return_base_url', 'http://localhost:3000');

        $this->store = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
            'currency' => 'usd',
            'stripe_account_id' => 'acct_test_merchant',
            'stripe_account_type' => 'express',
            'stripe_details_submitted' => true,
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => true,
            'stripe_onboarded_at' => now(),
        ]);

        $this->user = User::factory()->customer()->verified()->create();

        $category = Category::create([
            'store_id' => $this->store->id,
            'slug' => 'test-category',
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'brand_id' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU',
            'price' => 100.00,
            'quantity' => 10,
            'is_active' => true,
        ]);

        $this->cart = Cart::create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
        ]);

        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 100.00,
        ]);

        $zone = ShippingZone::create([
            'store_id' => $this->store->id,
            'name' => 'Test Zone',
            'countries' => ['US'],
            'is_active' => true,
        ]);

        $this->shippingMethod = ShippingMethod::create([
            'store_id' => $this->store->id,
            'shipping_zone_id' => $zone->id,
            'name' => 'Standard Shipping',
            'code' => 'standard',
            'price' => 10.00,
            'is_active' => true,
        ]);
    }

    public function test_creates_payment_intent_with_split_payment_when_store_can_receive_payments(): void
    {
        $mockStripe = Mockery::mock(StripeClient::class);
        $mockPaymentIntentsService = Mockery::mock();
        $mockStripe->paymentIntents = $mockPaymentIntentsService;

        $capturedParams = null;
        $mockPaymentIntentsService
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($params) use (&$capturedParams) {
                $capturedParams = $params;
                return true;
            })
            ->andReturn((object) [
                'id' => 'pi_test_split_payment',
                'client_secret' => 'pi_test_split_payment_secret',
                'status' => 'requires_payment_method',
            ]);

        $this->app->instance(StripeClient::class, $mockStripe);

        $service = $this->app->make(EnhancedCheckoutService::class);

        $result = $service->createPaymentIntent(
            user: $this->user,
            store: $this->store,
            shippingAddress: [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Test St',
                'city' => 'Test City',
                'state' => 'TS',
                'postal_code' => '12345',
                'country' => 'US',
                'phone' => '+1234567890',
            ],
            billingAddress: [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Test St',
                'city' => 'Test City',
                'state' => 'TS',
                'postal_code' => '12345',
                'country' => 'US',
                'phone' => '+1234567890',
            ],
            shippingMethodId: $this->shippingMethod->id
        );

        $this->assertNotNull($capturedParams);
        $this->assertEquals(21000, $capturedParams['amount']); // $210 total (200 + 10 shipping)
        $this->assertEquals('usd', $capturedParams['currency']);
        
        // Verify split payment parameters
        $this->assertArrayHasKey('application_fee_amount', $capturedParams);
        $this->assertArrayHasKey('transfer_data', $capturedParams);
        
        // Platform fee should be 3% of $210 = $6.30 = 630 cents
        $this->assertEquals(630, $capturedParams['application_fee_amount']);
        $this->assertEquals('acct_test_merchant', $capturedParams['transfer_data']['destination']);
    }

    public function test_blocks_checkout_when_store_cannot_receive_payments(): void
    {
        $this->store->update([
            'stripe_charges_enabled' => false,
            'stripe_payouts_enabled' => false,
        ]);

        $mockStripe = Mockery::mock(StripeClient::class);
        $this->app->instance(StripeClient::class, $mockStripe);

        $service = $this->app->make(EnhancedCheckoutService::class);

        $this->expectException(BaseApiException::class);
        $this->expectExceptionMessage('This store has not completed payment setup');

        $service->createPaymentIntent(
            user: $this->user,
            store: $this->store,
            shippingAddress: [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Test St',
                'city' => 'Test City',
                'state' => 'TS',
                'postal_code' => '12345',
                'country' => 'US',
                'phone' => '+1234567890',
            ],
            billingAddress: [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Test St',
                'city' => 'Test City',
                'state' => 'TS',
                'postal_code' => '12345',
                'country' => 'US',
                'phone' => '+1234567890',
            ],
            shippingMethodId: $this->shippingMethod->id
        );
    }

    public function test_blocks_checkout_when_store_has_no_stripe_account(): void
    {
        $this->store->update([
            'stripe_account_id' => null,
            'stripe_details_submitted' => false,
            'stripe_charges_enabled' => false,
            'stripe_payouts_enabled' => false,
        ]);

        $mockStripe = Mockery::mock(StripeClient::class);
        $this->app->instance(StripeClient::class, $mockStripe);

        $service = $this->app->make(EnhancedCheckoutService::class);

        $this->expectException(BaseApiException::class);
        $this->expectExceptionMessage('This store has not completed payment setup');

        $service->createPaymentIntent(
            user: $this->user,
            store: $this->store,
            shippingAddress: [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Test St',
                'city' => 'Test City',
                'state' => 'TS',
                'postal_code' => '12345',
                'country' => 'US',
                'phone' => '+1234567890',
            ],
            billingAddress: [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Test St',
                'city' => 'Test City',
                'state' => 'TS',
                'postal_code' => '12345',
                'country' => 'US',
                'phone' => '+1234567890',
            ],
            shippingMethodId: $this->shippingMethod->id
        );
    }

    public function test_calculates_platform_fee_correctly_for_different_amounts(): void
    {
        $mockStripe = Mockery::mock(StripeClient::class);
        $mockPaymentIntentsService = Mockery::mock();
        $mockStripe->paymentIntents = $mockPaymentIntentsService;

        $testCases = [
            ['total' => 50.00, 'expected_fee' => 150],   // 3% of $50 = $1.50
            ['total' => 100.00, 'expected_fee' => 300],  // 3% of $100 = $3.00
            ['total' => 99.99, 'expected_fee' => 300],   // 3% of $99.99 = $2.9997 rounded to $3.00
        ];

        foreach ($testCases as $testCase) {
            $capturedParams = null;
            $mockPaymentIntentsService
                ->shouldReceive('create')
                ->once()
                ->withArgs(function ($params) use (&$capturedParams) {
                    $capturedParams = $params;
                    return true;
                })
                ->andReturn((object) [
                    'id' => 'pi_test_' . uniqid(),
                    'client_secret' => 'pi_test_secret_' . uniqid(),
                    'status' => 'requires_payment_method',
                ]);

            $this->app->instance(StripeClient::class, $mockStripe);

            // Update cart item price for test case
            $this->cart->items->first()->update([
                'unit_price' => $testCase['total'] - 10.00, // Subtract shipping
            ]);

            $service = $this->app->make(EnhancedCheckoutService::class);

            $service->createPaymentIntent(
                user: $this->user,
                store: $this->store,
                shippingAddress: [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address_line_1' => '123 Test St',
                    'city' => 'Test City',
                    'state' => 'TS',
                    'postal_code' => '12345',
                    'country' => 'US',
                    'phone' => '+1234567890',
                ],
                billingAddress: [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address_line_1' => '123 Test St',
                    'city' => 'Test City',
                    'state' => 'TS',
                    'postal_code' => '12345',
                    'country' => 'US',
                    'phone' => '+1234567890',
                ],
                shippingMethodId: $this->shippingMethod->id
            );

            $this->assertEquals(
                $testCase['expected_fee'],
                $capturedParams['application_fee_amount'],
                "Failed for total: {$testCase['total']}"
            );
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
