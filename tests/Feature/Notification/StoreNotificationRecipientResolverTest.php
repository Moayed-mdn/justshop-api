<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Enums\Notification\NotificationCategoryEnum;
use App\Enums\PermissionEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Models\User;
use App\Services\Notification\StoreNotificationRecipientResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Covers the product's explicit recipient-targeting rule:
 * - Store Admin receives every category.
 * - Store Staff only receives a category if they hold the associated
 *   permission for that store.
 * - ADMIN_ONLY categories never reach staff.
 *
 * See docs/notifications/ARCHITECTURE.md §2.
 */
class StoreNotificationRecipientResolverTest extends TestCase
{
    use RefreshDatabase;

    private StoreNotificationRecipientResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->resolver = $this->app->make(StoreNotificationRecipientResolver::class);
    }

    private function activeStore(): Store
    {
        return Store::factory()->create([
            'is_active' => true,
            'status' => StoreStatusEnum::ACTIVE,
        ]);
    }

    public function test_store_admin_receives_every_category(): void
    {
        $store = $this->activeStore();
        $admin = User::factory()->create();
        $store->users()->attach($admin->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        foreach (NotificationCategoryEnum::cases() as $category) {
            $recipients = $this->resolver->resolve($store, $category);

            $this->assertTrue(
                $recipients->contains('id', $admin->id),
                "Store Admin should receive category [{$category->value}]"
            );
        }
    }

    public function test_staff_only_receives_categories_matching_their_permissions(): void
    {
        $store = $this->activeStore();

        // Today's real 'staff' role holds order.view, product.view, and
        // invoice.view simultaneously (see PermissionSeeder) — so it
        // qualifies for all three non-admin-only categories at once.
        $staff = User::factory()->create();
        $store->users()->attach($staff->id, ['role' => StoreRoleEnum::STAFF->value]);

        $order = $this->resolver->resolve($store, NotificationCategoryEnum::ORDER);
        $inventory = $this->resolver->resolve($store, NotificationCategoryEnum::INVENTORY);
        $finance = $this->resolver->resolve($store, NotificationCategoryEnum::FINANCE);

        $this->assertTrue($order->contains('id', $staff->id));
        $this->assertTrue($inventory->contains('id', $staff->id));
        $this->assertTrue($finance->contains('id', $staff->id));
    }

    public function test_admin_only_category_never_reaches_staff(): void
    {
        $store = $this->activeStore();

        $admin = User::factory()->create();
        $store->users()->attach($admin->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $staff = User::factory()->create();
        $store->users()->attach($staff->id, ['role' => StoreRoleEnum::STAFF->value]);

        $recipients = $this->resolver->resolve($store, NotificationCategoryEnum::ADMIN_ONLY);

        $this->assertTrue($recipients->contains('id', $admin->id));
        $this->assertFalse($recipients->contains('id', $staff->id));
    }

    /**
     * Demonstrates the forward-compatibility the architecture doc calls
     * out: today every staff member shares one flat 'staff' role, but the
     * moment a store introduces a differentiated role (e.g. a hypothetical
     * "inventory_staff" holding only product.view), the resolver correctly
     * narrows recipients with zero notification-code changes — because it
     * defers to the real permission system rather than hardcoding
     * "staff = staff".
     */
    public function test_a_differentiated_staff_role_only_receives_its_own_permitted_categories(): void
    {
        $inventoryOnlyRole = Role::create(['name' => 'inventory_only_staff', 'guard_name' => 'web']);
        $inventoryOnlyRole->givePermissionTo(Permission::findByName(PermissionEnum::PRODUCT_VIEW, 'web'));

        $store = $this->activeStore();
        $inventoryStaff = User::factory()->create();
        $store->users()->attach($inventoryStaff->id, ['role' => 'inventory_only_staff']);

        $inventory = $this->resolver->resolve($store, NotificationCategoryEnum::INVENTORY);
        $order = $this->resolver->resolve($store, NotificationCategoryEnum::ORDER);

        $this->assertTrue($inventory->contains('id', $inventoryStaff->id));
        $this->assertFalse($order->contains('id', $inventoryStaff->id));
    }

    public function test_inactive_membership_is_excluded(): void
    {
        $store = $this->activeStore();
        $admin = User::factory()->create();
        $store->users()->attach($admin->id, [
            'role' => StoreRoleEnum::STORE_ADMIN->value,
            'lifecycle_status' => 'revoked',
        ]);

        $recipients = $this->resolver->resolve($store, NotificationCategoryEnum::ORDER);

        $this->assertFalse($recipients->contains('id', $admin->id));
    }
}
