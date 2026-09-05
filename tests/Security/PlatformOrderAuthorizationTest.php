<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Policies\OrderPolicy;
use App\Policies\PlatformOrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Platform Order Authorization Security Tests
 * 
 * Critical security tests for Wave 6 order authorization refactor.
 * Tests merchant vs platform authority separation.
 */
class PlatformOrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;
    private Store $storeB;
    private Order $orderStoreA;
    private Order $orderStoreB;
    private User $merchantUserStoreA;
    private User $merchantUserStoreB;
    private User $platformUserReadOnly;
    private User $platformUserFullAccess;
    private User $superAdminNoPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);

        // Create stores
        $this->storeA = Store::factory()->create(['name' => 'Store A']);
        $this->storeB = Store::factory()->create(['name' => 'Store B']);

        // Create orders
        $this->orderStoreA = Order::factory()->for($this->storeA)->create([
            'order_number' => 'ORD-A-001',
            'subtotal' => 100,
            'total' => 100,
        ]);

        $this->orderStoreB = Order::factory()->for($this->storeB)->create([
            'order_number' => 'ORD-B-001',
            'subtotal' => 200,
            'total' => 200,
        ]);

        // Create merchant user for Store A with order permissions
        $this->merchantUserStoreA = User::factory()->merchant()->create();
        $this->merchantUserStoreA->stores()->attach($this->storeA, ['role' => 'store_admin']);
        $this->merchantUserStoreA->givePermissionTo([
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::ORDER_UPDATE_STATUS,
            PermissionEnum::ORDER_CANCEL,
            PermissionEnum::ORDER_REFUND,
        ]);

        // Create merchant user for Store B with order permissions
        $this->merchantUserStoreB = User::factory()->merchant()->create();
        $this->merchantUserStoreB->stores()->attach($this->storeB, ['role' => 'store_admin']);
        $this->merchantUserStoreB->givePermissionTo([
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::ORDER_UPDATE_STATUS,
            PermissionEnum::ORDER_CANCEL,
            PermissionEnum::ORDER_REFUND,
        ]);

        // Create platform user with read-only access
        $this->platformUserReadOnly = User::factory()->create();
        $this->platformUserReadOnly->assignRole(RoleEnum::SUPER_ADMIN->value);
        $this->platformUserReadOnly->givePermissionTo([
            PermissionEnum::PLATFORM_ORDER_VIEW,
        ]);

        // Create platform user with full access
        $this->platformUserFullAccess = User::factory()->create();
        $this->platformUserFullAccess->assignRole(RoleEnum::SUPER_ADMIN->value);
        $this->platformUserFullAccess->givePermissionTo([
            PermissionEnum::PLATFORM_ORDER_VIEW,
            PermissionEnum::PLATFORM_ORDER_UPDATE_STATUS,
            PermissionEnum::PLATFORM_ORDER_CANCEL,
            PermissionEnum::PLATFORM_ORDER_REFUND,
        ]);

        // Create SUPER_ADMIN with NO permissions (to test permission enforcement)
        $this->superAdminNoPermissions = User::factory()->create();
        $this->superAdminNoPermissions->assignRole(RoleEnum::SUPER_ADMIN->value);
        // Intentionally no permissions granted
    }

    // ============================================================
    // MERCHANT TESTS
    // ============================================================

    /** @test */
    public function merchant_with_order_view_can_view_own_store_orders(): void
    {
        $policy = app(OrderPolicy::class);

        $canView = $policy->viewAny($this->merchantUserStoreA, $this->storeA);

        $this->assertTrue($canView, 'Merchant should be able to view own store orders');
    }

    /** @test */
    public function merchant_with_order_view_cannot_view_another_store_orders(): void
    {
        $policy = app(OrderPolicy::class);

        $canView = $policy->viewAny($this->merchantUserStoreA, $this->storeB);

        $this->assertFalse($canView, 'Merchant should NOT be able to view another store orders');
    }

    /** @test */
    public function merchant_with_order_update_status_can_update_own_store_order_status(): void
    {
        $policy = app(OrderPolicy::class);

        $canUpdate = $policy->updateStatus($this->merchantUserStoreA, $this->orderStoreA);

        $this->assertTrue($canUpdate, 'Merchant should be able to update own store order status');
    }

    /** @test */
    public function merchant_with_order_cancel_can_cancel_own_store_order(): void
    {
        $policy = app(OrderPolicy::class);

        $canCancel = $policy->cancel($this->merchantUserStoreA, $this->orderStoreA);

        $this->assertTrue($canCancel, 'Merchant should be able to cancel own store order');
    }

    /** @test */
    public function merchant_with_order_refund_can_refund_own_store_order(): void
    {
        $policy = app(OrderPolicy::class);

        $canRefund = $policy->refund($this->merchantUserStoreA, $this->orderStoreA);

        $this->assertTrue($canRefund, 'Merchant should be able to refund own store order');
    }

    /** @test */
    public function merchant_permissions_do_not_provide_platform_access(): void
    {
        // Merchant has merchant order.view but not platform.order.view
        $platformPolicy = app(PlatformOrderPolicy::class);

        $this->expectException(\App\Exceptions\Authorization\PermissionDeniedException::class);

        $platformPolicy->viewAny($this->merchantUserStoreA);
    }

    // ============================================================
    // PLATFORM READ TESTS
    // ============================================================

    /** @test */
    public function platform_actor_with_platform_order_view_can_list_orders_from_multiple_stores(): void
    {
        $policy = app(PlatformOrderPolicy::class);

        $canViewAny = $policy->viewAny($this->platformUserReadOnly);

        $this->assertTrue($canViewAny, 'Platform actor with platform.order.view should be able to list all orders');
    }

    /** @test */
    public function platform_actor_with_platform_order_view_can_view_order_from_any_store(): void
    {
        $policy = app(PlatformOrderPolicy::class);

        $canViewStoreA = $policy->view($this->platformUserReadOnly, $this->orderStoreA);
        $canViewStoreB = $policy->view($this->platformUserReadOnly, $this->orderStoreB);

        $this->assertTrue($canViewStoreA, 'Platform actor should be able to view Store A order');
        $this->assertTrue($canViewStoreB, 'Platform actor should be able to view Store B order');
    }

    /** @test */
    public function platform_actor_without_platform_order_view_cannot_view_platform_orders(): void
    {
        $userWithoutPermission = User::factory()->create();
        $userWithoutPermission->assignRole(RoleEnum::SUPER_ADMIN->value);
        // No platform.order.view permission

        $policy = app(PlatformOrderPolicy::class);

        $this->expectException(\App\Exceptions\Authorization\PermissionDeniedException::class);

        $policy->viewAny($userWithoutPermission);
    }

    /** @test */
    public function platform_actor_without_platform_order_view_cannot_access_platform_order_detail(): void
    {
        $userWithoutPermission = User::factory()->create();
        $userWithoutPermission->assignRole(RoleEnum::SUPER_ADMIN->value);
        // No platform.order.view permission

        $policy = app(PlatformOrderPolicy::class);

        $this->expectException(\App\Exceptions\Authorization\PermissionDeniedException::class);

        $policy->view($userWithoutPermission, $this->orderStoreA);
    }

    /** @test */
    public function platform_access_does_not_require_merchant_membership_in_the_store(): void
    {
        // Platform user is NOT a member of Store A or Store B
        $this->assertFalse(
            $this->platformUserReadOnly->stores()->where('store_id', $this->storeA->id)->exists(),
            'Platform user should not be a member of Store A'
        );

        $policy = app(PlatformOrderPolicy::class);

        $canView = $policy->view($this->platformUserReadOnly, $this->orderStoreA);

        $this->assertTrue($canView, 'Platform actor should access orders without store membership');
    }

    // ============================================================
    // PLATFORM WRITE TESTS (READ VS WRITE SEPARATION)
    // ============================================================

    /** @test */
    public function platform_actor_with_platform_order_view_only_cannot_update_order_status(): void
    {
        // Has platform.order.view but NOT platform.order.update_status
        $policy = app(PlatformOrderPolicy::class);

        $this->expectException(\App\Exceptions\Authorization\PermissionDeniedException::class);

        $policy->updateStatus($this->platformUserReadOnly, $this->orderStoreA);
    }

    /** @test */
    public function platform_actor_with_platform_order_view_only_cannot_cancel_orders(): void
    {
        // Has platform.order.view but NOT platform.order.cancel
        $policy = app(PlatformOrderPolicy::class);

        $this->expectException(\App\Exceptions\Authorization\PermissionDeniedException::class);

        $policy->cancel($this->platformUserReadOnly, $this->orderStoreA);
    }

    /** @test */
    public function platform_actor_with_platform_order_view_only_cannot_refund_orders(): void
    {
        // Has platform.order.view but NOT platform.order.refund
        $policy = app(PlatformOrderPolicy::class);

        $this->expectException(\App\Exceptions\Authorization\PermissionDeniedException::class);

        $policy->refund($this->platformUserReadOnly, $this->orderStoreA);
    }

    /** @test */
    public function platform_actor_with_platform_order_update_status_can_update_status(): void
    {
        $policy = app(PlatformOrderPolicy::class);

        $canUpdate = $policy->updateStatus($this->platformUserFullAccess, $this->orderStoreA);

        $this->assertTrue($canUpdate, 'Platform actor with platform.order.update_status should be able to update status');
    }

    /** @test */
    public function platform_actor_with_platform_order_cancel_can_cancel(): void
    {
        $policy = app(PlatformOrderPolicy::class);

        $canCancel = $policy->cancel($this->platformUserFullAccess, $this->orderStoreA);

        $this->assertTrue($canCancel, 'Platform actor with platform.order.cancel should be able to cancel');
    }

    /** @test */
    public function platform_actor_with_platform_order_refund_can_refund(): void
    {
        $policy = app(PlatformOrderPolicy::class);

        $canRefund = $policy->refund($this->platformUserFullAccess, $this->orderStoreA);

        $this->assertTrue($canRefund, 'Platform actor with platform.order.refund should be able to refund');
    }

    // ============================================================
    // SUPER_ADMIN SECURITY TESTS (CRITICAL)
    // ============================================================

    /** @test */
    public function super_admin_without_platform_order_permission_cannot_use_platform_order_endpoints(): void
    {
        $policy = app(PlatformOrderPolicy::class);

        $this->expectException(\App\Exceptions\Authorization\PermissionDeniedException::class);

        $policy->viewAny($this->superAdminNoPermissions);
    }

    /** @test */
    public function super_admin_without_merchant_membership_cannot_use_merchant_order_endpoints_merely_because_they_are_super_admin(): void
    {
        // SUPER_ADMIN is NOT a member of Store A
        $this->assertFalse(
            $this->superAdminNoPermissions->stores()->where('store_id', $this->storeA->id)->exists(),
            'SUPER_ADMIN should not be a member of Store A'
        );

        $merchantPolicy = app(OrderPolicy::class);

        $canView = $merchantPolicy->viewAny($this->superAdminNoPermissions, $this->storeA);

        $this->assertFalse($canView, 'SUPER_ADMIN without store membership should NOT bypass merchant OrderPolicy');
    }

    /** @test */
    public function super_admin_does_not_bypass_merchant_order_policy_automatically(): void
    {
        $merchantPolicy = app(OrderPolicy::class);

        // SUPER_ADMIN without store membership trying to view merchant order
        $canView = $merchantPolicy->view($this->superAdminNoPermissions, $this->orderStoreA);

        $this->assertFalse($canView, 'SUPER_ADMIN should NOT automatically bypass merchant order authorization');
    }

    /** @test */
    public function super_admin_gains_platform_order_access_only_when_platform_permission_is_granted(): void
    {
        $policy = app(PlatformOrderPolicy::class);

        // Without permission
        try {
            $policy->viewAny($this->superAdminNoPermissions);
            $this->fail('Should have thrown PermissionDeniedException');
        } catch (\App\Exceptions\Authorization\PermissionDeniedException $e) {
            // Expected
        }

        // Grant permission
        $this->superAdminNoPermissions->givePermissionTo(PermissionEnum::PLATFORM_ORDER_VIEW);

        // Now should work
        $canViewAny = $policy->viewAny($this->superAdminNoPermissions);

        $this->assertTrue($canViewAny, 'SUPER_ADMIN with platform.order.view should be able to access platform orders');
    }

    /** @test */
    public function super_admin_with_platform_order_view_but_no_mutation_permissions_cannot_mutate_orders(): void
    {
        // Grant only view permission
        $this->superAdminNoPermissions->givePermissionTo(PermissionEnum::PLATFORM_ORDER_VIEW);

        $policy = app(PlatformOrderPolicy::class);

        // Can view
        $canView = $policy->view($this->superAdminNoPermissions, $this->orderStoreA);
        $this->assertTrue($canView, 'Should be able to view with platform.order.view');

        // Cannot update status
        $this->expectException(\App\Exceptions\Authorization\PermissionDeniedException::class);
        $policy->updateStatus($this->superAdminNoPermissions, $this->orderStoreA);
    }

    // ============================================================
    // TENANT ISOLATION / IDOR TESTS
    // ============================================================

    /** @test */
    public function store_a_merchant_cannot_retrieve_store_b_order(): void
    {
        $merchantPolicy = app(OrderPolicy::class);

        // Store A merchant trying to view Store B order
        $canView = $merchantPolicy->view($this->merchantUserStoreA, $this->orderStoreB);

        $this->assertFalse($canView, 'Store A merchant should NOT be able to view Store B order');
    }

    /** @test */
    public function merchant_without_platform_authority_cannot_access_platform_endpoints(): void
    {
        $platformPolicy = app(PlatformOrderPolicy::class);

        $this->expectException(\App\Exceptions\Authorization\PermissionDeniedException::class);

        $platformPolicy->viewAny($this->merchantUserStoreA);
    }

    /** @test */
    public function platform_endpoints_can_intentionally_access_store_b_orders_only_with_platform_authority_and_platform_order_view(): void
    {
        $policy = app(PlatformOrderPolicy::class);

        // Platform user with permission can access any store's orders
        $canViewStoreB = $policy->view($this->platformUserReadOnly, $this->orderStoreB);

        $this->assertTrue($canViewStoreB, 'Platform actor with platform.order.view should access Store B orders');
    }

    // ============================================================
    // CUSTOMER ORDER ACCESS TESTS
    // ============================================================

    /** @test */
    public function customer_can_view_their_own_order(): void
    {
        $customer = User::factory()->customer()->create();
        $customerOrder = Order::factory()->for($this->storeA)->for($customer, 'user')->create([
            'order_number' => 'ORD-CUST-001',
            'subtotal' => 50,
            'total' => 50,
        ]);

        $merchantPolicy = app(OrderPolicy::class);

        $canView = $merchantPolicy->view($customer, $customerOrder);

        $this->assertTrue($canView, 'Customer should be able to view their own order');
    }

    /** @test */
    public function customer_can_cancel_their_own_order(): void
    {
        $customer = User::factory()->customer()->create();
        $customerOrder = Order::factory()->for($this->storeA)->for($customer, 'user')->create([
            'order_number' => 'ORD-CUST-002',
            'subtotal' => 50,
            'total' => 50,
        ]);

        $merchantPolicy = app(OrderPolicy::class);

        $canCancel = $merchantPolicy->cancel($customer, $customerOrder);

        $this->assertTrue($canCancel, 'Customer should be able to cancel their own order');
    }

    /** @test */
    public function customer_cannot_view_another_customers_order(): void
    {
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();

        $customer1Order = Order::factory()->for($this->storeA)->for($customer1, 'user')->create([
            'order_number' => 'ORD-CUST-003',
            'subtotal' => 50,
            'total' => 50,
        ]);

        $merchantPolicy = app(OrderPolicy::class);

        $canView = $merchantPolicy->view($customer2, $customer1Order);

        $this->assertFalse($canView, 'Customer should NOT be able to view another customer order');
    }
}
