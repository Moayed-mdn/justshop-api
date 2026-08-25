<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Billing\BillingAccountStatusEnum;
use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\PermissionEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\BillingAccount;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Covers App\Http\Controllers\Api\Merchant\AdminBrandController via the
 * real HTTP routes registered in routes/api/v1/merchant/admin.php:
 *
 *   GET    /api/v1/merchant/stores/{store}/brands
 *   GET    /api/v1/merchant/stores/{store}/brands/{brand}
 *   POST   /api/v1/merchant/stores/{store}/brands
 *   PATCH  /api/v1/merchant/stores/{store}/brands/{brand}
 *   DELETE /api/v1/merchant/stores/{store}/brands/{brand}
 *   PATCH  /api/v1/merchant/stores/{store}/brands/{brand}/restore
 *
 * NOTE ON SUBSCRIPTIONS: see the docblock on
 * tests/Feature/Admin/AdminCategoryManagementTest.php — write routes here
 * carry the same `subscription.active` middleware, so grantActiveSubscription()
 * is required before any create/update/delete/restore call.
 */
class AdminBrandManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function makeStoreAdmin(): array
    {
        $owner = User::factory()->merchant()->create();
        $store = Store::factory()->for($owner, 'owner')->create();
        $owner->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        return [$owner, $store];
    }

    private function grantActiveSubscription(Store $store): void
    {
        $billingAccount = BillingAccount::create([
            'owner_user_id' => $store->owner_id,
            'billing_email' => 'billing+' . $store->owner_id . '@example.test',
            'status' => BillingAccountStatusEnum::ACTIVE->value,
        ]);

        StoreEntitlementSnapshot::create([
            'store_id' => $store->id,
            'billing_account_id' => $billingAccount->id,
            'entitlement_status' => EntitlementStatusEnum::ENTITLED->value,
            'features' => [],
            'products_count' => 0,
        ]);
    }

    private function grantBrandPermissionsToStoreAdminRole(): void
    {
        $permissions = [
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::BRAND_CREATE,
            PermissionEnum::BRAND_UPDATE,
            PermissionEnum::BRAND_DELETE,
            PermissionEnum::BRAND_RESTORE,
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate(StoreRoleEnum::STORE_ADMIN->value, 'web')->syncPermissions($permissions);
    }

    private function makeStoreStaffWithPermissions(Store $store, array $permissions): User
    {
        $staff = User::factory()->merchant()->create();
        $staff->stores()->attach($store->id, ['role' => StoreRoleEnum::STAFF->value]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate(StoreRoleEnum::STAFF->value, 'web')->syncPermissions($permissions);

        return $staff;
    }

    private function brandPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Acme Gear',
            'slug' => 'acme-gear',
        ], $overrides);
    }

    // ── Happy path ───────────────────────────────────────────────

    public function test_store_admin_with_permission_can_create_brand(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/brands",
            $this->brandPayload(),
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Acme Gear')
            ->assertJsonPath('data.store_id', $store->id);

        $this->assertDatabaseHas('brands', [
            'store_id' => $store->id,
            'slug' => 'acme-gear',
        ]);
    }

    public function test_store_admin_with_permission_can_list_brands_for_their_store(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        Brand::factory()->count(4)->create(['store_id' => $store->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/brands")
            ->assertStatus(200)
            ->assertJsonPath('meta.pagination.total', 4);
    }

    public function test_store_admin_with_permission_can_view_a_single_brand(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $brand = Brand::factory()->create(['store_id' => $store->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/brands/{$brand->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $brand->id);
    }

    public function test_store_admin_with_permission_can_update_a_brand(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $brand = Brand::factory()->create(['store_id' => $store->id, 'name' => 'Old Name']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->patchJson(
            "/api/v1/merchant/stores/{$store->id}/brands/{$brand->id}",
            $this->brandPayload(['name' => 'New Name', 'slug' => $brand->slug]),
        );

        $response->assertStatus(200)->assertJsonPath('data.name', 'New Name');
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'New Name']);
    }

    public function test_store_admin_with_permission_can_delete_brand_without_products(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $brand = Brand::factory()->create(['store_id' => $store->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->deleteJson("/api/v1/merchant/stores/{$store->id}/brands/{$brand->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('brands', ['id' => $brand->id]);
    }

    public function test_store_admin_with_permission_can_restore_a_soft_deleted_brand(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $brand = Brand::factory()->create(['store_id' => $store->id]);
        $brand->delete();

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->patchJson("/api/v1/merchant/stores/{$store->id}/brands/{$brand->id}/restore")
            ->assertStatus(200);

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'deleted_at' => null]);
    }

    // ── Authorization failures ───────────────────────────────────

    public function test_guest_cannot_access_brands(): void
    {
        [, $store] = $this->makeStoreAdmin();

        $this->getJson("/api/v1/merchant/stores/{$store->id}/brands")
            ->assertStatus(401);
    }

    public function test_store_staff_without_permission_is_forbidden_from_creating_brand(): void
    {
        [, $store] = $this->makeStoreAdmin();
        $this->grantActiveSubscription($store);
        $staff = $this->makeStoreStaffWithPermissions($store, []);

        Sanctum::actingAs($staff, ['*'], 'merchant');

        $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/brands",
            $this->brandPayload(),
        )->assertStatus(403);
    }

    public function test_merchant_who_is_not_a_member_of_the_store_cannot_list_its_brands(): void
    {
        [, $store] = $this->makeStoreAdmin();
        $outsider = User::factory()->merchant()->create();
        Store::factory()->for($outsider, 'owner')->create();

        Sanctum::actingAs($outsider, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/brands")
            ->assertStatus(403);
    }

    // ── Validation failures ──────────────────────────────────────

    public function test_creating_brand_without_required_fields_fails_validation(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->postJson("/api/v1/merchant/stores/{$store->id}/brands", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function test_creating_brand_with_invalid_slug_format_fails_validation(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/brands",
            $this->brandPayload(['slug' => 'Not Valid!']),
        )->assertStatus(422);
    }

    // ── Edge cases ────────────────────────────────────────────────

    public function test_cannot_delete_brand_that_has_products(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $brand = Brand::factory()->create(['store_id' => $store->id]);
        Product::create([
            'store_id' => $store->id,
            'category_id' => null,
            'brand_id' => $brand->id,
            'product_variant_id' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->deleteJson("/api/v1/merchant/stores/{$store->id}/brands/{$brand->id}")
            ->assertStatus(422);
    }

    public function test_store_admin_cannot_view_brand_belonging_to_another_store(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $otherStore = Store::factory()->create();
        $foreignBrand = Brand::factory()->create(['store_id' => $otherStore->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/brands/{$foreignBrand->id}")
            ->assertStatus(404);
    }

    public function test_brand_index_does_not_leak_brands_from_other_stores(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        Brand::factory()->count(2)->create(['store_id' => $store->id]);
        Brand::factory()->count(3)->create(['store_id' => Store::factory()->create()->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/brands")
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_brand_slug_uniqueness_is_enforced_globally_across_stores(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantBrandPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $otherStore = Store::factory()->create();
        Brand::factory()->create(['store_id' => $otherStore->id, 'slug' => 'shared-brand-slug']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        // NOTE: `brands.slug` is a globally unique column (see the
        // create_brands_table migration and CreateBrandRequest::rules()),
        // not scoped per store. This test documents that real constraint.
        $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/brands",
            $this->brandPayload(['slug' => 'shared-brand-slug']),
        )->assertStatus(422);
    }
}
