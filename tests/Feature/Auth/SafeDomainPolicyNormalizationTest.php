<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\MembershipPolicy;
use App\Policies\TagPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SafeDomainPolicyNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalized_domain_policies_enforce_tenant_isolation_and_unauthorized_actor_denial(): void
    {
        $storeAdmin = $this->createStoreUser(
            roleName: RoleEnum::STORE_ADMIN->value,
            permissions: [
                PermissionEnum::BRAND_VIEW,
                PermissionEnum::CATEGORY_VIEW,
                PermissionEnum::TAG_VIEW,
                PermissionEnum::DASHBOARD_VIEW,
            ],
        );

        $accessibleStore = Store::factory()->for($storeAdmin, 'owner')->create();
        $otherStore = Store::factory()->create();
        $storeAdmin->stores()->attach($accessibleStore->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $this->assertTrue(app(BrandPolicy::class)->viewAny($storeAdmin, $accessibleStore));
        $this->assertFalse(app(BrandPolicy::class)->viewAny($storeAdmin, $otherStore));
        $this->assertTrue(app(CategoryPolicy::class)->viewAny($storeAdmin, $accessibleStore));
        $this->assertFalse(app(CategoryPolicy::class)->viewAny($storeAdmin, $otherStore));
        $this->assertTrue(app(TagPolicy::class)->viewAny($storeAdmin, $accessibleStore));
        $this->assertFalse(app(TagPolicy::class)->viewAny($storeAdmin, $otherStore));
        $this->assertTrue(app(DashboardPolicy::class)->viewStats($storeAdmin, $accessibleStore));
        $this->assertFalse(app(DashboardPolicy::class)->viewStats($storeAdmin, $otherStore));

        $memberWithoutPermissions = User::factory()->merchant()->create();
        $memberWithoutPermissions->stores()->attach($accessibleStore->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $this->assertFalse(app(BrandPolicy::class)->viewAny($memberWithoutPermissions, $accessibleStore));
        $this->assertFalse(app(CategoryPolicy::class)->viewAny($memberWithoutPermissions, $accessibleStore));
        $this->assertFalse(app(TagPolicy::class)->viewAny($memberWithoutPermissions, $accessibleStore));
        $this->assertFalse(app(DashboardPolicy::class)->viewStats($memberWithoutPermissions, $accessibleStore));
    }

    public function test_super_admin_has_no_implicit_bypass_for_normalized_safe_domains(): void
    {
        // SUPER_ADMIN grants no implicit authorization bypass. Without store
        // membership or an active, governed impersonation session, access to
        // these tenant-owned resources must be denied exactly like any other
        // non-member -- including for previously "safe domain" abilities.
        $role = Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');

        $superAdmin = User::factory()->merchant()->create();
        $superAdmin->assignRole($role);
        $superAdmin = $superAdmin->fresh();
        $store = Store::factory()->create();

        $this->assertFalse(Gate::forUser($superAdmin)->check('create', [\App\Models\Brand::class, $store]));
        $this->assertFalse(Gate::forUser($superAdmin)->check('restore', [\App\Models\Category::class, $store]));
        $this->assertFalse(Gate::forUser($superAdmin)->check('delete', [\App\Models\Tag::class, $store]));
        $this->assertFalse(Gate::forUser($superAdmin)->check('viewTopProducts', [\App\Support\Auth\DashboardAuthorization::class, $store]));
    }

    public function test_membership_admin_route_uses_user_model_policy_owner_for_store_scoped_access(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(PermissionEnum::USER_VIEW, 'web');
        $storeAdminRole = Role::findOrCreate(StoreRoleEnum::STORE_ADMIN->value, 'web');
        $storeAdminRole->syncPermissions([PermissionEnum::USER_VIEW]);

        $storeAdmin = User::factory()->merchant()->create();
        $managedUser = User::factory()->merchant()->create();

        $store = Store::factory()->for($storeAdmin, 'owner')->create();
        $storeAdmin->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $managedUser->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $this->assertTrue(app(MembershipPolicy::class)->viewAny($storeAdmin, $store));
        $this->assertTrue(Gate::forUser($storeAdmin)->check('viewAny', [User::class, $store]));

        Sanctum::actingAs($storeAdmin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/users")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['id' => $storeAdmin->id])
            ->assertJsonPath('data.0.id', $managedUser->id)
            ->assertJsonPath('data.0.role', StoreRoleEnum::STORE_ADMIN->value);
    }

    public function test_policy_telemetry_records_middleware_vs_policy_parity_for_normalized_brand_route(): void
    {
        Log::spy();

        $user = $this->createStoreUser(
            roleName: RoleEnum::STORE_ADMIN->value,
            permissions: [PermissionEnum::BRAND_VIEW],
        );

        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        Route::middleware(['api', 'auth:sanctum', 'permission:' . PermissionEnum::BRAND_VIEW, 'store.context'])
            ->get('/api/v1/test-wave25/brand-parity/{store}', function (Request $request) {
                return response()->json([
                    'allowed' => app(BrandPolicy::class)->viewAny($request->user(), app('currentStore')),
                ]);
            });

        $this->actingAs($user);

        $response = $this->getJson("/api/v1/test-wave25/brand-parity/{$store->id}");

        $response->assertOk()->assertJsonPath('allowed', true);

        Log::shouldHaveReceived('info')->with(
            'authorization.policy.decision',
            Mockery::on(fn (array $context): bool => ($context['policy'] ?? null) === BrandPolicy::class
                && ($context['ability'] ?? null) === 'viewAny'
                && ($context['middleware_capability'] ?? null) === PermissionEnum::BRAND_VIEW
                && ($context['middleware_permission_allowed'] ?? null) === true
                && ($context['middleware_policy_parity'] ?? null) === true
                && ($context['dual_authorization_path'] ?? null) === true
                && (($context['store_context']['id'] ?? null) === $store->id)),
        )->atLeast()->once();
    }

    private function createStoreUser(string $roleName, array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->merchant()->create();
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
