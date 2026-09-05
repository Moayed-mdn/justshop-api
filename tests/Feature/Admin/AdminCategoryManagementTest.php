<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Billing\BillingAccountStatusEnum;
use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\PermissionEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\BillingAccount;
use App\Models\Category;
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
 * Covers App\Http\Controllers\Api\Merchant\AdminCategoryController via the
 * real HTTP routes registered in routes/api/v1/merchant/admin.php:
 *
 *   GET    /api/v1/merchant/stores/{store}/categories
 *   GET    /api/v1/merchant/stores/{store}/categories/{category}
 *   POST   /api/v1/merchant/stores/{store}/categories
 *   PATCH  /api/v1/merchant/stores/{store}/categories/{category}
 *   DELETE /api/v1/merchant/stores/{store}/categories/{category}
 *   PATCH  /api/v1/merchant/stores/{store}/categories/{category}/restore
 *
 * NOTE ON SUBSCRIPTIONS: every write route (POST/PATCH/DELETE) in
 * routes/api/v1/merchant/admin.php additionally carries the
 * `subscription.active` middleware, which calls
 * App\Services\Entitlement\FeatureGateService::ensureWriteAccess() and
 * throws (-> HTTP 402) whenever the store has no
 * App\Models\StoreEntitlementSnapshot row. Neither StoreFactory nor
 * App\Observers\StoreObserver creates one automatically, and no factory
 * exists for StoreEntitlementSnapshot/BillingAccount, so every test that
 * exercises a write endpoint must build that snapshot itself via
 * grantActiveSubscription(). See the final report for why this matters for
 * tests/Feature/Admin/UserCreationTest.php too.
 */
class AdminCategoryManagementTest extends TestCase
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

    /**
     * Gives a store write access by creating the BillingAccount +
     * StoreEntitlementSnapshot rows that FeatureGateService::ensureWriteAccess()
     * requires. Required for any test that hits a POST/PATCH/DELETE
     * admin route (see class docblock).
     */
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

    private function grantCategoryPermissionsToStoreAdminRole(): void
    {
        $permissions = [
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::CATEGORY_CREATE,
            PermissionEnum::CATEGORY_UPDATE,
            PermissionEnum::CATEGORY_DELETE,
            PermissionEnum::CATEGORY_RESTORE,
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

    private function categoryPayload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'summer-collection',
            'translations' => [
                ['locale' => 'en', 'name' => 'Summer Collection', 'slug' => 'summer-collection-en'],
                ['locale' => 'ar', 'name' => 'مجموعة الصيف', 'slug' => 'summer-collection-ar'],
            ],
        ], $overrides);
    }

    // ── Happy path ───────────────────────────────────────────────

    public function test_store_admin_with_permission_can_create_category(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/categories",
            $this->categoryPayload(),
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'summer-collection')
            ->assertJsonPath('data.store_id', $store->id);

        $this->assertDatabaseHas('categories', [
            'store_id' => $store->id,
            'slug' => 'summer-collection',
        ]);

        $this->assertDatabaseHas('category_translations', [
            'locale' => 'en',
            'name' => 'Summer Collection',
        ]);
    }

    public function test_store_admin_with_permission_can_list_categories_for_their_store(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        Category::factory()->count(3)->create(['store_id' => $store->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->getJson("/api/v1/merchant/stores/{$store->id}/categories");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.pagination.total', 3);
    }

    public function test_store_admin_with_permission_can_view_a_single_category(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $category = Category::factory()->create(['store_id' => $store->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->getJson("/api/v1/merchant/stores/{$store->id}/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $category->id);
    }

    public function test_store_admin_with_permission_can_update_a_category(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $category = Category::factory()->create(['store_id' => $store->id, 'slug' => 'old-slug']);
        $category->translations()->create(['locale' => 'en', 'name' => 'Old', 'slug' => 'old-en']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->patchJson(
            "/api/v1/merchant/stores/{$store->id}/categories/{$category->id}",
            $this->categoryPayload(['slug' => 'new-slug']),
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'new-slug');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'slug' => 'new-slug',
        ]);
    }

    public function test_store_admin_with_permission_can_delete_category_without_children_or_products(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $category = Category::factory()->create(['store_id' => $store->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->deleteJson("/api/v1/merchant/stores/{$store->id}/categories/{$category->id}");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_store_admin_with_permission_can_restore_a_soft_deleted_category(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $category = Category::factory()->create(['store_id' => $store->id]);
        $category->delete();

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->patchJson(
            "/api/v1/merchant/stores/{$store->id}/categories/{$category->id}/restore",
        );

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    public function test_store_staff_with_explicit_permission_can_view_categories(): void
    {
        [, $store] = $this->makeStoreAdmin();
        $staff = $this->makeStoreStaffWithPermissions($store, [PermissionEnum::CATEGORY_VIEW]);
        Category::factory()->create(['store_id' => $store->id]);

        Sanctum::actingAs($staff, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/categories")
            ->assertStatus(200);
    }

    // ── Authorization failures ───────────────────────────────────

    public function test_guest_cannot_access_categories(): void
    {
        [, $store] = $this->makeStoreAdmin();

        $this->getJson("/api/v1/merchant/stores/{$store->id}/categories")
            ->assertStatus(401);
    }

    public function test_store_staff_without_permission_is_forbidden_from_creating_category(): void
    {
        [, $store] = $this->makeStoreAdmin();
        $this->grantActiveSubscription($store);
        $staff = $this->makeStoreStaffWithPermissions($store, []);

        Sanctum::actingAs($staff, ['*'], 'merchant');

        $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/categories",
            $this->categoryPayload(),
        )->assertStatus(403);
    }

    public function test_merchant_who_is_not_a_member_of_the_store_cannot_view_its_categories(): void
    {
        [, $store] = $this->makeStoreAdmin();
        $outsider = User::factory()->merchant()->create();
        Store::factory()->for($outsider, 'owner')->create();

        Sanctum::actingAs($outsider, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/categories")
            ->assertStatus(403);
    }

    // ── Validation failures ──────────────────────────────────────

    public function test_creating_category_without_required_fields_fails_validation(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->postJson("/api/v1/merchant/stores/{$store->id}/categories", []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);
    }

    public function test_creating_category_with_invalid_slug_format_fails_validation(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/categories",
            $this->categoryPayload(['slug' => 'Not A Valid Slug!']),
        );

        $response->assertStatus(422);
    }

    // ── Edge cases ────────────────────────────────────────────────

    public function test_cannot_delete_category_that_has_child_categories(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $parent = Category::factory()->create(['store_id' => $store->id]);
        Category::factory()->create(['store_id' => $store->id, 'parent_id' => $parent->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->deleteJson("/api/v1/merchant/stores/{$store->id}/categories/{$parent->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('categories', ['id' => $parent->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_category_that_has_products(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $category = Category::factory()->create(['store_id' => $store->id]);
        Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'brand_id' => null,
            'product_variant_id' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->deleteJson("/api/v1/merchant/stores/{$store->id}/categories/{$category->id}")
            ->assertStatus(422);
    }

    public function test_store_admin_cannot_view_category_belonging_to_another_store(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $otherStore = Store::factory()->create();
        $foreignCategory = Category::factory()->create(['store_id' => $otherStore->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/categories/{$foreignCategory->id}")
            ->assertStatus(404);
    }

    public function test_category_index_does_not_leak_categories_from_other_stores(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        Category::factory()->count(2)->create(['store_id' => $store->id]);
        Category::factory()->count(5)->create(['store_id' => Store::factory()->create()->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/categories")
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_creating_category_with_parent_from_another_store_returns_not_found(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $otherStore = Store::factory()->create();
        $foreignParent = Category::factory()->create(['store_id' => $otherStore->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/categories",
            $this->categoryPayload(['parent_id' => $foreignParent->id]),
        );

        // The parent_id passes the global `exists:categories,id` validation rule
        // (the FormRequest does not scope it to the current store), so the
        // failure only surfaces later at the Action layer, which does scope
        // the parent lookup by store and throws CategoryNotFoundException.
        $response->assertStatus(404);
    }

    public function test_updating_category_cannot_set_itself_as_its_own_parent(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $category = Category::factory()->create(['store_id' => $store->id]);
        $category->translations()->create(['locale' => 'en', 'name' => 'Self', 'slug' => 'self-en']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->patchJson(
            "/api/v1/merchant/stores/{$store->id}/categories/{$category->id}",
            $this->categoryPayload(['parent_id' => $category->id]),
        );

        $response->assertStatus(422);
    }

    public function test_category_slug_uniqueness_is_enforced_globally_across_stores(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantCategoryPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $otherStore = Store::factory()->create();
        Category::factory()->create(['store_id' => $otherStore->id, 'slug' => 'shared-slug']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        // NOTE: the `categories.slug` column is unique with no store scoping
        // (see database/migrations/2025_10_09_140024_create_categories_table.php
        // and CreateCategoryRequest::rules()). Two different stores cannot use
        // the same top-level category slug. This test documents that real,
        // possibly-surprising constraint rather than asserting a "should be
        // isolated per store" behavior that the code does not implement.
        $response = $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/categories",
            $this->categoryPayload(['slug' => 'shared-slug']),
        );

        $response->assertStatus(422);
    }
}
