<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Platform Order Security Integration Tests
 * 
 * Tests actual HTTP requests to verify platform order authorization.
 * These are the CRITICAL security tests.
 */
class PlatformOrderSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdminNoPermissions;
    private User $platformUserWithView;
    private User $merchantUser;
    private Store $store;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable observers
        Store::unsetEventDispatcher();
        Order::unsetEventDispatcher();

        // Seed permissions
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);

        // Create store and order
        $this->store = Store::factory()->create();
        $this->order = Order::factory()->for($this->store)->create([
            'order_number' => 'TEST-001',
            'subtotal' => 100,
            'total' => 100,
        ]);

        // SUPER_ADMIN without any platform order permissions
        $this->superAdminNoPermissions = User::factory()->create();
        $this->superAdminNoPermissions->assignRole(RoleEnum::SUPER_ADMIN->value);
        // Intentionally NO platform order permissions

        // Platform user with view permission only
        $this->platformUserWithView = User::factory()->create();
        $this->platformUserWithView->assignRole(RoleEnum::SUPER_ADMIN->value);
        $this->platformUserWithView->givePermissionTo(PermissionEnum::PLATFORM_ORDER_VIEW);

        // Merchant user (not a platform actor)
        $this->merchantUser = User::factory()->merchant()->create();
        $this->merchantUser->stores()->attach($this->store, ['role' => 'store_admin']);
        $this->merchantUser->givePermissionTo([
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::ORDER_UPDATE_STATUS,
        ]);
    }

    /** @test */
    public function super_admin_without_platform_order_view_cannot_list_platform_orders(): void
    {
        $response = $this->actingAs($this->superAdminNoPermissions, 'sanctum')
            ->getJson('/api/v1/platform/orders');

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_without_platform_order_view_cannot_view_platform_order_detail(): void
    {
        $response = $this->actingAs($this->superAdminNoPermissions, 'sanctum')
            ->getJson("/api/v1/platform/orders/{$this->order->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_without_platform_order_update_status_cannot_update_order_status(): void
    {
        $response = $this->actingAs($this->superAdminNoPermissions, 'sanctum')
            ->patchJson("/api/v1/platform/orders/{$this->order->id}/status", [
                'status' => 'processing',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_without_platform_order_cancel_cannot_cancel_order(): void
    {
        $response = $this->actingAs($this->superAdminNoPermissions, 'sanctum')
            ->patchJson("/api/v1/platform/orders/{$this->order->id}/cancel");

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_without_platform_order_refund_cannot_refund_order(): void
    {
        $response = $this->actingAs($this->superAdminNoPermissions, 'sanctum')
            ->postJson("/api/v1/platform/orders/{$this->order->id}/refund", [
                'amount' => 100.00,
                'reason' => 'Test refund',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function platform_user_with_view_permission_can_list_orders(): void
    {
        $response = $this->actingAs($this->platformUserWithView, 'sanctum')
            ->getJson('/api/v1/platform/orders');

        $response->assertStatus(200);
    }

    /** @test */
    public function platform_user_with_view_permission_can_view_order_detail(): void
    {
        $response = $this->actingAs($this->platformUserWithView, 'sanctum')
            ->getJson("/api/v1/platform/orders/{$this->order->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $this->order->id);
    }

    /** @test */
    public function platform_user_with_view_only_cannot_update_order_status(): void
    {
        $response = $this->actingAs($this->platformUserWithView, 'sanctum')
            ->patchJson("/api/v1/platform/orders/{$this->order->id}/status", [
                'status' => 'processing',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function platform_user_with_view_only_cannot_cancel_order(): void
    {
        $response = $this->actingAs($this->platformUserWithView, 'sanctum')
            ->patchJson("/api/v1/platform/orders/{$this->order->id}/cancel");

        $response->assertStatus(403);
    }

    /** @test */
    public function platform_user_with_view_only_cannot_refund_order(): void
    {
        $response = $this->actingAs($this->platformUserWithView, 'sanctum')
            ->postJson("/api/v1/platform/orders/{$this->order->id}/refund", [
                'amount' => 100.00,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function merchant_user_without_platform_authority_cannot_access_platform_orders(): void
    {
        $response = $this->actingAs($this->merchantUser, 'sanctum')
            ->getJson('/api/v1/platform/orders');

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_without_merchant_membership_cannot_access_merchant_order_endpoint(): void
    {
        // SUPER_ADMIN is not a member of the store
        $response = $this->actingAs($this->superAdminNoPermissions, 'sanctum')
            ->getJson("/api/v1/merchant/stores/{$this->store->id}/orders");

        // Should be denied because no store membership
        $response->assertStatus(403);
    }

    /** @test */
    public function platform_user_with_permission_can_access_orders_from_multiple_stores(): void
    {
        // Create second store and order
        $store2 = Store::factory()->create(['name' => 'Store Two']);
        $order2 = Order::factory()->for($store2)->create([
            'order_number' => 'TEST-002',
            'subtotal' => 200,
            'total' => 200,
        ]);

        // Give platform user all permissions
        $platformUser = User::factory()->create();
        $platformUser->assignRole(RoleEnum::SUPER_ADMIN->value);
        $platformUser->givePermissionTo([
            PermissionEnum::PLATFORM_ORDER_VIEW,
            PermissionEnum::PLATFORM_ORDER_UPDATE_STATUS,
            PermissionEnum::PLATFORM_ORDER_CANCEL,
            PermissionEnum::PLATFORM_ORDER_REFUND,
        ]);

        // Can access orders from both stores
        $response = $this->actingAs($platformUser, 'sanctum')
            ->getJson('/api/v1/platform/orders');

        $response->assertStatus(200);

        // Should see both orders
        $orderIds = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($this->order->id, $orderIds);
        $this->assertContains($order2->id, $orderIds);

        // Can view order from store 2
        $response = $this->actingAs($platformUser, 'sanctum')
            ->getJson("/api/v1/platform/orders/{$order2->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $order2->id);
    }
}
