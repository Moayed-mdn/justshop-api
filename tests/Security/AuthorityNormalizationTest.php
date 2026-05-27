<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthorityNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');
    }

    /**
     * Verify that LeadPolicy (Platform Resource) allows Super Admin but denies others.
     */
    public function test_super_admin_can_access_leads_without_bypass(): void
    {
        /** @var User $admin */
        $admin = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->create();

        $this->actingAs($admin);
        
        $this->assertTrue($admin->can('view', $lead));
        $this->assertTrue($admin->can('viewAny', Lead::class));
    }

    public function test_merchant_cannot_access_leads(): void
    {
        /** @var User $merchant */
        $merchant = User::factory()->merchant()->create();
        $lead = Lead::factory()->create();

        $this->actingAs($merchant);
        
        $this->assertFalse($merchant->can('view', $lead));
    }

    /**
     * Verify that TagPolicy (Merchant Resource) denies Super Admin WITHOUT impersonation.
     */
    public function test_super_admin_denied_merchant_resource_without_impersonation(): void
    {
        /** @var User $admin */
        $admin = User::factory()->superAdmin()->create();
        $store = Store::factory()->create();

        $this->actingAs($admin);
        
        // Tags are merchant resources. Admin is NOT a member and NOT impersonating.
        $this->assertFalse($admin->can('viewAny', [\App\Models\Tag::class, $store]));
    }

    /**
     * Verify that StoreRepository denies Super Admin WITHOUT impersonation.
     */
    public function test_store_repository_filters_out_stores_for_non_impersonating_admin(): void
    {
        /** @var User $admin */
        $admin = User::factory()->superAdmin()->create();
        Store::factory()->count(3)->create(['is_active' => true]);

        $repository = new \App\Repositories\Store\StoreRepository();
        
        // Mocking the request to ensure no session/impersonation exists
        $this->instance('request', request());

        $stores = $repository->getAccessibleStores($admin);
        
        $this->assertCount(0, $stores);
    }

    /**
     * Verify that legacy platform routes now require platform.authority (which requires SUPER_ADMIN).
     */
    public function test_platform_routes_require_authentication_and_authority(): void
    {
        // 1. Unauthenticated -> 401
        $this->getJson(route('platform.leads.index'))->assertStatus(401);

        // 2. Merchant (No platform authority) -> 403
        /** @var User $merchant */
        $merchant = User::factory()->merchant()->create();
        $this->actingAs($merchant)->getJson(route('platform.leads.index'))->assertStatus(403);

        // 3. Super Admin -> 200
        /** @var User $admin */
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->getJson(route('platform.leads.index'))->assertStatus(200);
    }

    /**
     * Step 3 Hardening: Verify strict guard enforcement.
     */
    public function test_merchant_cannot_access_platform_routes_via_fallback(): void
    {
        /** @var User $merchant */
        $merchant = User::factory()->merchant()->create();

        // Merchant authenticated via 'merchant' guard should NOT be allowed on platform routes
        $response = $this->actingAs($merchant, 'merchant')
            ->getJson(route('platform.leads.index'));

        $response->assertStatus(403);
    }

    /**
     * Step 3 Hardening: Verify impersonation rotation.
     */
    public function test_impersonation_activation_regenerates_session(): void
    {
        /** @var User $admin */
        $admin = User::factory()->superAdmin()->create();
        /** @var User $target */
        $target = User::factory()->merchant()->create();

        $manager = app(\App\Services\Platform\Impersonation\ImpersonationLifecycleManager::class);
        $impersonation = $manager->request($admin, $target, 'Testing');

        $this->actingAs($admin);
        $oldSessionId = session()->getId();

        $manager->activate(request(), $impersonation);

        $this->assertNotEquals($oldSessionId, session()->getId());
    }
}
