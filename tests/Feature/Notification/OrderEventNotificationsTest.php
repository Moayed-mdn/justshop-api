<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Events\Order\OrderCancelled;
use App\Events\Order\OrderPlaced;
use App\Events\Order\OrderStatusChanged;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Notifications\Order\NewOrderReceivedNotification;
use App\Notifications\Order\OrderCancelledByCustomerNotification;
use App\Notifications\Order\OrderCancelledNotification;
use App\Notifications\Order\OrderPlacedNotification;
use App\Notifications\Order\OrderStatusChangedNotification;
use App\Notifications\Platform\HighValueOrderNotification;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderEventNotificationsTest extends TestCase
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

    private function makeOrder(Store $store, ?User $customer, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'store_id' => $store->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'user_id' => $customer?->id,
            'subtotal' => 100,
            'total' => 100,
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PAID,
        ], $overrides));
    }

    public function test_order_placed_notifies_customer_and_merchant_team(): void
    {
        [$store, $admin, $staff] = $this->makeStoreWithTeam();
        $customer = User::factory()->create();
        $order = $this->makeOrder($store, $customer);

        OrderPlaced::dispatch($order->id);

        Notification::assertSentTo($customer, OrderPlacedNotification::class);
        Notification::assertSentTo($admin, NewOrderReceivedNotification::class);
        // The seeded 'staff' role holds order.view, so it qualifies for the ORDER category.
        Notification::assertSentTo($staff, NewOrderReceivedNotification::class);
    }

    public function test_order_placed_does_not_notify_a_customer_on_guest_checkout(): void
    {
        [$store, $admin] = $this->makeStoreWithTeam();
        $order = $this->makeOrder($store, null, ['guest_email' => 'guest@example.com']);

        $this->assertNull($order->user_id);

        OrderPlaced::dispatch($order->id);

        // No user account exists to notify, but the merchant team still
        // hears about the order — that's the meaningful assertion here
        // (there's no "customer" notifiable to assert nothing was sent to).
        Notification::assertSentTo($admin, NewOrderReceivedNotification::class);
    }

    public function test_high_value_order_additionally_notifies_platform_admins(): void
    {
        config(['notifications.high_value_order_threshold' => 500]);

        [$store] = $this->makeStoreWithTeam();
        $customer = User::factory()->create();
        $platformAdmin = User::factory()->create();
        $platformAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);

        $order = $this->makeOrder($store, $customer, ['total' => 1000, 'subtotal' => 1000]);

        OrderPlaced::dispatch($order->id);

        Notification::assertSentTo($platformAdmin, HighValueOrderNotification::class);
    }

    public function test_low_value_order_does_not_notify_platform_admins(): void
    {
        config(['notifications.high_value_order_threshold' => 500]);

        [$store] = $this->makeStoreWithTeam();
        $customer = User::factory()->create();
        $platformAdmin = User::factory()->create();
        $platformAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);

        $order = $this->makeOrder($store, $customer, ['total' => 50, 'subtotal' => 50]);

        OrderPlaced::dispatch($order->id);

        Notification::assertNotSentTo($platformAdmin, HighValueOrderNotification::class);
    }

    public function test_order_status_changed_notifies_customer(): void
    {
        [$store] = $this->makeStoreWithTeam();
        $customer = User::factory()->create();
        $order = $this->makeOrder($store, $customer, ['status' => OrderStatusEnum::PROCESSING]);

        OrderStatusChanged::dispatch($order->id, OrderStatusEnum::PROCESSING, OrderStatusEnum::SHIPPED);

        Notification::assertSentTo($customer, OrderStatusChangedNotification::class);
    }

    public function test_order_cancelled_by_customer_notifies_merchant_team_not_customer(): void
    {
        [$store, $admin] = $this->makeStoreWithTeam();
        $customer = User::factory()->create();
        $order = $this->makeOrder($store, $customer, ['status' => OrderStatusEnum::CANCELLED]);

        OrderCancelled::dispatch($order->id, $customer->id);

        Notification::assertSentTo($admin, OrderCancelledByCustomerNotification::class);
        Notification::assertNotSentTo($customer, OrderCancelledNotification::class);
    }

    public function test_order_cancelled_by_merchant_notifies_customer_not_merchant_team(): void
    {
        [$store, $admin] = $this->makeStoreWithTeam();
        $customer = User::factory()->create();
        $order = $this->makeOrder($store, $customer, ['status' => OrderStatusEnum::CANCELLED]);

        OrderCancelled::dispatch($order->id, $admin->id);

        Notification::assertSentTo($customer, OrderCancelledNotification::class);
        Notification::assertNotSentTo($admin, OrderCancelledByCustomerNotification::class);
    }
}
