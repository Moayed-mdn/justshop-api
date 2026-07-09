<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Exceptions\Authorization\PermissionDeniedException;
use App\Models\Store;
use App\Models\User;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\TagPolicy;
use App\Policies\MembershipPolicy;
use App\Policies\OrderPolicy;
use App\Policies\Cms\Marketing\Store\StoreMarketingPagePolicy;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffPermissionErrorMessagesTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedStaffPermissions();
        $this->seedSuperAdminRole();

        $this->store = Store::factory()->create();

        $this->staffUser = $this->createStaffUser();
    }

    // ── Bug Condition: Staff receives contextual error messages ──

    public function test_staff_create_product_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);

        app(ProductPolicy::class)->create($this->staffUser, $this->store);
    }

    public function test_staff_update_product_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);

        app(ProductPolicy::class)->update($this->staffUser, $this->store);
    }

    public function test_staff_delete_product_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);

        app(ProductPolicy::class)->delete($this->staffUser, $this->store);
    }

    public function test_staff_create_category_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);

        app(CategoryPolicy::class)->create($this->staffUser, $this->store);
    }

    public function test_staff_delete_category_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);

        app(CategoryPolicy::class)->delete($this->staffUser, $this->store);
    }

    public function test_staff_create_brand_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);

        app(BrandPolicy::class)->create($this->staffUser, $this->store);
    }

    public function test_staff_delete_tag_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);

        app(TagPolicy::class)->delete($this->staffUser, $this->store);
    }

    public function test_staff_update_order_status_throws_permission_denied_exception(): void
    {
        $order = Order::factory()->for($this->store)->create([
            'order_number' => 'ORD-TEST-' . uniqid(),
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->expectException(PermissionDeniedException::class);

        app(OrderPolicy::class)->updateStatus($this->staffUser, $order);
    }

    public function test_staff_refund_order_throws_permission_denied_exception(): void
    {
        $order = Order::factory()->for($this->store)->create([
            'order_number' => 'ORD-TEST-' . uniqid(),
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->expectException(PermissionDeniedException::class);

        app(OrderPolicy::class)->refund($this->staffUser, $order);
    }

    public function test_staff_create_user_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);

        app(MembershipPolicy::class)->create($this->staffUser, $this->store);
    }

    public function test_staff_delete_user_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);

        app(MembershipPolicy::class)->delete($this->staffUser, $this->store);
    }

    public function test_permission_denied_exception_carries_resource_and_action_context(): void
    {
        try {
            app(CategoryPolicy::class)->delete($this->staffUser, $this->store);
            $this->fail('PermissionDeniedException was not thrown');
        } catch (PermissionDeniedException $e) {
            $this->assertSame('category', $e->getResource());
            $this->assertSame('delete', $e->getAction());
            $this->assertSame(PermissionEnum::CATEGORY_DELETE, $e->getPermission());
        }
    }

    public function test_permission_denied_exception_message_is_contextual(): void
    {
        try {
            app(CategoryPolicy::class)->delete($this->staffUser, $this->store);
            $this->fail('PermissionDeniedException was not thrown');
        } catch (PermissionDeniedException $e) {
            $this->assertStringContainsString('delete', $e->getMessage());
            $this->assertStringContainsStringIgnoringCase('categor', $e->getMessage());
            $this->assertSame(403, $e->render(request())->getStatusCode());
        }
    }

    // ── Preservation: Existing behavior unchanged ──

    public function test_store_admin_can_manage_resources(): void
    {
        $admin = $this->createStoreAdminUser();

        $this->assertTrue(app(ProductPolicy::class)->create($admin, $this->store));
        $this->assertTrue(app(CategoryPolicy::class)->create($admin, $this->store));
        $this->assertTrue(app(CategoryPolicy::class)->update($admin, $this->store));
        $this->assertTrue(app(BrandPolicy::class)->create($admin, $this->store));
        $this->assertTrue(app(TagPolicy::class)->create($admin, $this->store));
        $this->assertTrue(app(MembershipPolicy::class)->viewAny($admin, $this->store));
    }

    public function test_super_admin_bypasses_all_policy_checks(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue(app(ProductPolicy::class)->create($superAdmin, $this->store));
        $this->assertTrue(app(CategoryPolicy::class)->delete($superAdmin, $this->store));
        $this->assertTrue(app(BrandPolicy::class)->update($superAdmin, $this->store));
        $this->assertTrue(app(TagPolicy::class)->delete($superAdmin, $this->store));
    }

    public function test_staff_can_view_resources(): void
    {
        $this->assertTrue(app(CategoryPolicy::class)->viewAny($this->staffUser, $this->store));
        $this->assertTrue(app(CategoryPolicy::class)->view($this->staffUser, $this->store));
        $this->assertTrue(app(BrandPolicy::class)->viewAny($this->staffUser, $this->store));
        $this->assertTrue(app(BrandPolicy::class)->view($this->staffUser, $this->store));
        $this->assertTrue(app(TagPolicy::class)->viewAny($this->staffUser, $this->store));
        $this->assertTrue(app(TagPolicy::class)->view($this->staffUser, $this->store));
        $this->assertTrue(app(ProductPolicy::class)->viewAny($this->staffUser, $this->store));
        $this->assertTrue(app(ProductPolicy::class)->view($this->staffUser, $this->store));
    }

    public function test_non_member_user_is_denied_without_exception(): void
    {
        $nonMember = User::factory()->merchant()->create();

        $this->assertFalse(app(CategoryPolicy::class)->create($nonMember, $this->store));
        $this->assertFalse(app(BrandPolicy::class)->update($nonMember, $this->store));
        $this->assertFalse(app(TagPolicy::class)->delete($nonMember, $this->store));
        $this->assertFalse(app(ProductPolicy::class)->create($nonMember, $this->store));
    }

    public function test_permission_denied_exception_has_access_denied_error_code(): void
    {
        try {
            app(TagPolicy::class)->delete($this->staffUser, $this->store);
            $this->fail('PermissionDeniedException was not thrown');
        } catch (PermissionDeniedException $e) {
            $render = $e->render(request());
            $content = $render->getData(true);

            $this->assertFalse($content['success']);
            $this->assertSame('ACCESS_DENIED', $content['code']);
            $this->assertSame(403, $render->getStatusCode());
        }
    }

    public function test_staff_create_page_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageMatches('/marketing pages/i');

        app(StoreMarketingPagePolicy::class)->create($this->staffUser, $this->store);
    }

    public function test_staff_update_page_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageMatches('/marketing pages/i');

        app(StoreMarketingPagePolicy::class)->update($this->staffUser, $this->store);
    }

    public function test_staff_delete_page_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageMatches('/marketing pages/i');

        app(StoreMarketingPagePolicy::class)->delete($this->staffUser, $this->store);
    }

    public function test_staff_publish_page_throws_permission_denied_exception(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageMatches('/marketing pages/i');

        app(StoreMarketingPagePolicy::class)->publish($this->staffUser, $this->store);
    }

    public function test_staff_cannot_view_pages(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageMatches('/marketing pages/i');

        app(StoreMarketingPagePolicy::class)->viewAny($this->staffUser, $this->store);
    }

    public function test_store_admin_can_manage_pages(): void
    {
        $admin = $this->createStoreAdminUser();

        $this->assertTrue(app(StoreMarketingPagePolicy::class)->create($admin, $this->store));
        $this->assertTrue(app(StoreMarketingPagePolicy::class)->update($admin, $this->store));
        $this->assertTrue(app(StoreMarketingPagePolicy::class)->delete($admin, $this->store));
        $this->assertTrue(app(StoreMarketingPagePolicy::class)->publish($admin, $this->store));
    }

    public function test_order_owner_can_cancel_own_order(): void
    {
        $owner = User::factory()->customer()->create();
        $order = Order::factory()->for($owner, 'user')->for($this->store)->create([
            'order_number' => 'ORD-TEST-' . uniqid(),
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->assertTrue(app(OrderPolicy::class)->cancel($owner, $order));
    }

    // ── Helpers ──

    private function seedStaffPermissions(): void
    {
        $viewPermissions = [
            PermissionEnum::PRODUCT_VIEW,
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::TAG_VIEW,
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::USER_VIEW,
        ];

        $marketingPermissions = [
            PermissionEnum::MARKETING_STORE_VIEW,
        ];

        foreach (array_merge($viewPermissions, $marketingPermissions) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $managePermissions = [
            PermissionEnum::PRODUCT_CREATE,
            PermissionEnum::PRODUCT_UPDATE,
            PermissionEnum::PRODUCT_DELETE,
            PermissionEnum::CATEGORY_CREATE,
            PermissionEnum::CATEGORY_UPDATE,
            PermissionEnum::CATEGORY_DELETE,
            PermissionEnum::BRAND_CREATE,
            PermissionEnum::BRAND_UPDATE,
            PermissionEnum::BRAND_DELETE,
            PermissionEnum::TAG_CREATE,
            PermissionEnum::TAG_UPDATE,
            PermissionEnum::TAG_DELETE,
            PermissionEnum::ORDER_UPDATE_STATUS,
            PermissionEnum::ORDER_CANCEL,
            PermissionEnum::ORDER_REFUND,
            PermissionEnum::USER_CREATE,
            PermissionEnum::USER_BLOCK,
            PermissionEnum::USER_DELETE,
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
            PermissionEnum::MARKETING_STORE_DELETE,
            PermissionEnum::MARKETING_STORE_PUBLISH,
        ];

        foreach ($managePermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate(RoleEnum::STAFF->value, 'web');
        $role->syncPermissions($viewPermissions);

        $adminRole = Role::findOrCreate(RoleEnum::STORE_ADMIN->value, 'web');
        $adminRole->syncPermissions(array_merge($viewPermissions, $marketingPermissions, $managePermissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createStaffUser(): User
    {
        $user = User::factory()->merchant()->create();
        $user->assignRole(RoleEnum::STAFF->value);
        $user->stores()->attach($this->store->id, ['role' => StoreRoleEnum::STAFF->value]);
        $user = $user->fresh();

        return $user;
    }

    private function seedSuperAdminRole(): void
    {
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createStoreAdminUser(): User
    {
        $user = User::factory()->merchant()->create();
        $user->assignRole(RoleEnum::STORE_ADMIN->value);
        $user->stores()->attach($this->store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user = $user->fresh();

        return $user;
    }
}
