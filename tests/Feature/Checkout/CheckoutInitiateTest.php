<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Enums\ErrorCode;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutInitiateTest extends TestCase
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
            'slug' => 'checkout-test-category',
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
            'sku' => 'SKU-CHECKOUT-TEST',
            'price' => 29.99,
            'quantity' => 10,
            'is_active' => true,
        ]);

        return [$store, $product, $variant];
    }

    public function test_guest_can_initiate_checkout_with_items_from_body(): void
    {
        [$store, , $variant] = $this->createStoreWithVariant();

        $this->mock(CheckoutService::class, function ($mock) use ($store, $variant): void {
            $mock->shouldReceive('createSessionForGuest')
                ->once()
                ->withArgs(function (int $storeId, array $items, $email) use ($store, $variant): bool {
                    return $storeId === $store->id
                        && count($items) === 1
                        && $items[0]['product_variant_id'] === $variant->id
                        && $items[0]['quantity'] === 2
                        && $email === null;
                })
                ->andReturn([
                    'session_id' => 'cs_test_guest',
                    'session_url' => 'https://checkout.stripe.com/cs_test_guest',
                ]);

            $mock->shouldReceive('createSessionForUser')
                ->never();
        });

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", [
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 2],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['session_id', 'session_url'],
            ]);
    }

    public function test_guest_checkout_fails_without_items(): void
    {
        [$store] = $this->createStoreWithVariant();

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', ErrorCode::VAL_001->value)
            ->assertJsonStructure(['errors' => ['items']]);
    }

    public function test_guest_checkout_fails_with_invalid_variant_id(): void
    {
        [$store] = $this->createStoreWithVariant();

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", [
            'items' => [
                ['product_variant_id' => 999999, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', ErrorCode::VAL_001->value)
            ->assertJsonStructure(['errors' => ['items.0.product_variant_id']]);
    }

    public function test_guest_checkout_accepts_optional_email(): void
    {
        [$store, , $variant] = $this->createStoreWithVariant();

        $this->mock(CheckoutService::class, function ($mock) use ($store, $variant): void {
            $mock->shouldReceive('createSessionForGuest')
                ->once()
                ->withArgs(function (int $storeId, array $items, $email): bool {
                    return $email === 'guest@example.com';
                })
                ->andReturn([
                    'session_id' => 'cs_test_email',
                    'session_url' => 'https://checkout.stripe.com/cs_test_email',
                ]);

            $mock->shouldReceive('createSessionForUser')
                ->never();
        });

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", [
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 1],
            ],
            'email' => 'guest@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_authenticated_user_can_initiate_checkout_with_cart_from_db(): void
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
            'quantity' => 3,
            'unit_price' => 29.99,
        ]);

        Sanctum::actingAs($user, ['*'], 'customer');

        $this->mock(CheckoutService::class, function ($mock) use ($store, $user): void {
            $mock->shouldReceive('createSessionForUser')
                ->once()
                ->withArgs(function (User $capturedUser, int $storeId) use ($user, $store): bool {
                    return $capturedUser->id === $user->id && $storeId === $store->id;
                })
                ->andReturn([
                    'session_id' => 'cs_test_auth',
                    'session_url' => 'https://checkout.stripe.com/cs_test_auth',
                ]);

            $mock->shouldReceive('createSessionForGuest')
                ->never();
        });

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['session_id', 'session_url'],
            ]);
    }

    public function test_authenticated_user_without_cart_gets_error(): void
    {
        [$store] = $this->createStoreWithVariant();

        $user = User::factory()->customer()->verified()->create();

        Sanctum::actingAs($user, ['*'], 'customer');

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_checkout_rejects_wrong_store_cart(): void
    {
        $storeA = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $storeB = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $categoryA = Category::query()->create([
            'store_id' => $storeA->id,
            'slug' => 'store-a-category',
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $variantA = ProductVariant::query()->create([
            'product_id' => Product::query()->create([
                'store_id' => $storeA->id,
                'category_id' => $categoryA->id,
                'brand_id' => null,
                'is_active' => true,
                'sort_order' => 0,
            ])->id,
            'sku' => 'SKU-A',
            'price' => 10.00,
            'quantity' => 5,
            'is_active' => true,
        ]);

        $user = User::factory()->customer()->verified()->create();

        $cart = Cart::query()->create([
            'user_id' => $user->id,
            'store_id' => $storeA->id,
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variantA->id,
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);

        Sanctum::actingAs($user, ['*'], 'customer');

        $this->mock(CheckoutService::class, function ($mock) use ($user, $storeB): void {
            $mock->shouldReceive('createSessionForUser')
                ->once()
                ->withArgs(function (User $capturedUser, int $storeId) use ($user, $storeB): bool {
                    return $capturedUser->id === $user->id && $storeId === $storeB->id;
                })
                ->andReturn([
                    'session_id' => 'cs_test_cross',
                    'session_url' => 'https://checkout.stripe.com/cs_test_cross',
                ]);

            $mock->shouldReceive('createSessionForGuest')
                ->never();
        });

        $response = $this->postJson("/api/v1/storefront/stores/{$storeB->id}/checkout");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
