<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Models\Store;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tenant Isolation Scaffolding
 * 
 * This test suite validates the "Absolute Laws" of tenant isolation.
 * These tests are intended to FAIL if isolation is breached.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * G1.1: Merchant Cross-Store Isolation
     * A merchant in Store A must not access Store B resources.
     */
    public function test_merchant_cannot_access_other_store_products(): void
    {
        /** @var User $merchantA */
        $merchantA = User::factory()->merchant()->create();
        $storeA = Store::factory()->create(['owner_id' => $merchantA->id]);
        $merchantA->stores()->attach($storeA->id, ['role' => \App\Enums\Store\StoreRoleEnum::STORE_ADMIN->value]);

        $storeB = Store::factory()->create();
        $productB = Product::factory()->create(['store_id' => $storeB->id]);

        // Attempt to access Store B product via Store A context (should be denied by route/policy)
        $this->actingAs($merchantA)
            ->getJson("/api/v1/admin/stores/{$storeB->id}/products")
            ->assertStatus(403);
    }

    public function test_merchant_cannot_access_other_store_orders(): void
    {
        /** @var User $merchantA */
        $merchantA = User::factory()->merchant()->create();
        $storeA = Store::factory()->create(['owner_id' => $merchantA->id]);
        $merchantA->stores()->attach($storeA->id, ['role' => \App\Enums\Store\StoreRoleEnum::STORE_ADMIN->value]);

        $storeB = Store::factory()->create();
        Order::factory()->create(['store_id' => $storeB->id]);

        // Attempt to access Store B orders via Store A's admin (should be denied)
        $this->actingAs($merchantA, 'merchant')
            ->getJson("/api/v1/merchant/stores/{$storeB->id}/orders")
            ->assertStatus(403);
    }

    /**
     * G2.1: Platform vs Merchant Boundary
     * Super Admin must not implicitly access merchant resources.
     */
    public function test_super_admin_cannot_implicitly_access_store_resources(): void
    {
        /** @var User $admin */
        $admin = User::factory()->superAdmin()->create();
        $store = Store::factory()->create();

        // Admin is NOT a member of $store and has NO active impersonation.
        // None of these tenant-owned resource policies may implicitly grant
        // access on the strength of the SUPER_ADMIN role alone.
        $this->assertFalse($admin->can('viewAny', [\App\Models\Tag::class, $store]));
        $this->assertFalse($admin->can('viewAny', [\App\Policies\ProductPolicy::class, $store]));
        $this->assertFalse($admin->can('viewAny', [\App\Policies\CategoryPolicy::class, $store]));
        $this->assertFalse($admin->can('viewAny', [\App\Policies\BrandPolicy::class, $store]));
        $this->assertFalse($admin->can('viewAny', [\App\Policies\MembershipPolicy::class, $store]));

        // A real HTTP request must be denied too, not just the raw policy call.
        $this->actingAs($admin, 'merchant')
            ->getJson("/api/v1/merchant/stores/{$store->id}/products")
            ->assertStatus(403);
    }

    /**
     * G3.1: Queue Job Isolation
     * Verify that context is cleared between job executions.
     */
    public function test_queue_workers_do_not_leak_store_context(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $user = User::factory()->create();
        $store = Store::factory()->create();

        // Simulate a prior job (or request) having bound tenant context into
        // the container, the way store-scoped code paths do.
        app()->instance('storeId', $store->id);
        app()->instance('currentStore', $store);

        $this->assertTrue(app()->bound('storeId'));
        $this->assertTrue(app()->bound('currentStore'));

        // QUEUE_CONNECTION=sync in phpunit.xml, so this runs (and fires the
        // registered Queue::after() listener) synchronously and immediately.
        \App\Jobs\Auth\SendWelcomeEmailJob::dispatch($user->id);

        // The Queue::after() listener in AppServiceProvider must have
        // cleared the tenant context so the next job (or request) handled
        // by this same worker does not inherit it.
        $this->assertFalse(app()->bound('storeId'));
        $this->assertFalse(app()->bound('currentStore'));
    }
}
