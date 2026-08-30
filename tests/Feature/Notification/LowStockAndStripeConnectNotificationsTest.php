<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Enums\Store\StoreRoleEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Events\Product\ProductVariantLowStock;
use App\Events\Store\StripeConnectStatusChanged;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Notifications\Product\ProductLowStockNotification;
use App\Notifications\Store\StripeConnectStatusChangedNotification;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LowStockAndStripeConnectNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Notification::fake();
    }

    private function makeStoreWithTeam(): array
    {
        $store = Store::factory()->create([
            'is_active' => true,
            'status' => StoreStatusEnum::ACTIVE,
        ]);

        $admin = User::factory()->create();
        $store->users()->attach($admin->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $staff = User::factory()->create();
        $store->users()->attach($staff->id, ['role' => StoreRoleEnum::STAFF->value]);

        return [$store, $admin, $staff];
    }

    private function makeVariant(Store $store, int $quantity, int $threshold): ProductVariant
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => 'Test Widget',
            'slug' => 'test-widget-'.$product->id,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.$product->id,
            'price' => 19.99,
            'quantity' => $quantity,
            'low_stock_threshold' => $threshold,
            'track_inventory' => true,
        ]);
    }

    public function test_low_stock_event_notifies_store_admin_and_inventory_permitted_staff(): void
    {
        [$store, $admin, $staff] = $this->makeStoreWithTeam();
        $variant = $this->makeVariant($store, quantity: 3, threshold: 5);

        ProductVariantLowStock::dispatch($variant->id, 3, 5);

        Notification::assertSentTo($admin, ProductLowStockNotification::class);
        // Seeded 'staff' role holds product.view, so it qualifies for INVENTORY.
        Notification::assertSentTo($staff, ProductLowStockNotification::class);
    }

    public function test_stripe_connect_newly_onboarded_notifies_admin_only(): void
    {
        [$store, $admin, $staff] = $this->makeStoreWithTeam();

        $event = new StripeConnectStatusChanged(
            storeId: $store->id,
            before: ['details_submitted' => false, 'charges_enabled' => false, 'payouts_enabled' => false],
            after: ['details_submitted' => true, 'charges_enabled' => true, 'payouts_enabled' => true],
        );

        StripeConnectStatusChanged::dispatch($event->storeId, $event->before, $event->after);

        Notification::assertSentTo($admin, StripeConnectStatusChangedNotification::class);
        // ADMIN_ONLY category — staff never receive this, regardless of their permissions.
        Notification::assertNotSentTo($staff, StripeConnectStatusChangedNotification::class);
    }

    public function test_stripe_connect_newly_restricted_notifies_admin_only(): void
    {
        [$store, $admin, $staff] = $this->makeStoreWithTeam();

        StripeConnectStatusChanged::dispatch(
            $store->id,
            ['details_submitted' => true, 'charges_enabled' => true, 'payouts_enabled' => true],
            ['details_submitted' => true, 'charges_enabled' => false, 'payouts_enabled' => false],
        );

        Notification::assertSentTo($admin, StripeConnectStatusChangedNotification::class);
        Notification::assertNotSentTo($staff, StripeConnectStatusChangedNotification::class);
    }

    public function test_stripe_connect_partial_flag_change_does_not_notify(): void
    {
        [$store, $admin] = $this->makeStoreWithTeam();

        // details_submitted flips but charges/payouts remain disabled — not
        // a meaningful transition, shouldn't generate a notification.
        StripeConnectStatusChanged::dispatch(
            $store->id,
            ['details_submitted' => false, 'charges_enabled' => false, 'payouts_enabled' => false],
            ['details_submitted' => true, 'charges_enabled' => false, 'payouts_enabled' => false],
        );

        Notification::assertNotSentTo($admin, StripeConnectStatusChangedNotification::class);
    }
}
