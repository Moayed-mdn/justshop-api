<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Billing\BillingAccountStatusEnum;
use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\PermissionEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\BillingAccount;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Covers App\Http\Controllers\Api\Merchant\AdminTagController via the real
 * HTTP routes registered in routes/api/v1/merchant/admin.php:
 *
 *   GET    /api/v1/merchant/stores/{store}/tags
 *   GET    /api/v1/merchant/stores/{store}/tags/{tag}
 *   POST   /api/v1/merchant/stores/{store}/tags
 *   PATCH  /api/v1/merchant/stores/{store}/tags/{tag}
 *   DELETE /api/v1/merchant/stores/{store}/tags/{tag}
 *
 * There is no restore endpoint for tags (unlike categories/brands).
 *
 * NOTE ON SUBSCRIPTIONS: see the docblock on
 * tests/Feature/Admin/AdminCategoryManagementTest.php — write routes here
 * carry the same `subscription.active` middleware.
 *
 * NOTE ON LOCALES: CreateTagRequest/UpdateTagRequest validate
 * `translations.*.locale` against
 * config('content.editable_locales', config('app.supported_locales', [])).
 * Neither config file is present in the audited codebase snapshot, so every
 * test that submits translations explicitly sets
 * config('content.editable_locales', [...]) to stay deterministic
 * regardless of the real environment's config — the same pattern already
 * used in tests/Feature/Admin/AdminProductDetailResourceTest.php.
 */
class AdminTagManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        config()->set('content.editable_locales', ['en', 'ar']);
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

    private function grantTagPermissionsToStoreAdminRole(): void
    {
        $permissions = [
            PermissionEnum::TAG_VIEW,
            PermissionEnum::TAG_CREATE,
            PermissionEnum::TAG_UPDATE,
            PermissionEnum::TAG_DELETE,
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

    private function tagPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'season',
            'translations' => [
                ['locale' => 'en', 'name' => 'Summer', 'slug' => 'summer-tag'],
            ],
        ], $overrides);
    }

    // ── Happy path ───────────────────────────────────────────────

    public function test_store_admin_with_permission_can_create_tag(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/tags",
            $this->tagPayload(),
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.store_id', $store->id)
            ->assertJsonPath('data.translations.en.name', 'Summer');

        $this->assertDatabaseHas('tags', ['store_id' => $store->id, 'type' => 'season']);
        $this->assertDatabaseHas('tag_translations', ['locale' => 'en', 'slug' => 'summer-tag']);
    }

    public function test_store_admin_with_permission_sees_store_owned_and_global_tags_in_index(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        Tag::factory()->create(['store_id' => $store->id]);
        Tag::factory()->create(['store_id' => null]); // global tag

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/tags")
            ->assertStatus(200)
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_store_admin_with_permission_can_view_a_single_store_owned_tag(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $tag = Tag::factory()->create(['store_id' => $store->id]);
        $tag->translations()->create(['locale' => 'en', 'name' => 'Winter', 'slug' => 'winter-tag']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/tags/{$tag->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $tag->id);
    }

    public function test_store_admin_with_permission_can_update_a_store_owned_tag(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $tag = Tag::factory()->create(['store_id' => $store->id, 'type' => 'general']);
        $tag->translations()->create(['locale' => 'en', 'name' => 'Old', 'slug' => 'old-tag-name']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->patchJson(
            "/api/v1/merchant/stores/{$store->id}/tags/{$tag->id}",
            ['type' => 'season'],
        );

        $response->assertStatus(200)->assertJsonPath('data.type', 'season');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'type' => 'season']);
    }

    public function test_store_admin_with_permission_can_delete_a_store_owned_tag(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $tag = Tag::factory()->create(['store_id' => $store->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->deleteJson("/api/v1/merchant/stores/{$store->id}/tags/{$tag->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
    }

    // ── Authorization failures ───────────────────────────────────

    public function test_guest_cannot_access_tags(): void
    {
        [, $store] = $this->makeStoreAdmin();

        $this->getJson("/api/v1/merchant/stores/{$store->id}/tags")
            ->assertStatus(401);
    }

    public function test_store_staff_without_permission_is_forbidden_from_creating_tag(): void
    {
        [, $store] = $this->makeStoreAdmin();
        $this->grantActiveSubscription($store);
        $staff = $this->makeStoreStaffWithPermissions($store, []);

        Sanctum::actingAs($staff, ['*'], 'merchant');

        $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/tags",
            $this->tagPayload(),
        )->assertStatus(403);
    }

    public function test_merchant_who_is_not_a_member_of_the_store_cannot_list_its_tags(): void
    {
        [, $store] = $this->makeStoreAdmin();
        $outsider = User::factory()->merchant()->create();
        Store::factory()->for($outsider, 'owner')->create();

        Sanctum::actingAs($outsider, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/tags")
            ->assertStatus(403);
    }

    // ── Validation failures ──────────────────────────────────────

    public function test_creating_tag_without_translations_fails_validation(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->postJson("/api/v1/merchant/stores/{$store->id}/tags", ['type' => 'general'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function test_creating_tag_with_locale_outside_editable_locales_fails_validation(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        config()->set('content.editable_locales', ['en', 'ar']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $response = $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/tags",
            $this->tagPayload(['translations' => [
                ['locale' => 'fr', 'name' => 'Été', 'slug' => 'ete-tag'],
            ]]),
        );

        $response->assertStatus(422);
    }

    // ── Edge cases ────────────────────────────────────────────────

    public function test_global_tags_cannot_be_updated_via_store_endpoint(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $globalTag = Tag::factory()->create(['store_id' => null]);
        $globalTag->translations()->create(['locale' => 'en', 'name' => 'Global', 'slug' => 'global-tag']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        // AdminTagRepository::findStoreOwnedTag() scopes strictly by
        // store_id = $storeId, so a global tag (store_id = null) is never
        // matched and mutation correctly 404s.
        $this->patchJson(
            "/api/v1/merchant/stores/{$store->id}/tags/{$globalTag->id}",
            ['type' => 'general'],
        )->assertStatus(404);
    }

    public function test_global_tags_cannot_be_deleted_via_store_endpoint(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        $globalTag = Tag::factory()->create(['store_id' => null]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->deleteJson("/api/v1/merchant/stores/{$store->id}/tags/{$globalTag->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('tags', ['id' => $globalTag->id, 'deleted_at' => null]);
    }

    public function test_tag_index_does_not_leak_store_owned_tags_from_other_stores(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        Tag::factory()->create(['store_id' => $store->id]);
        Tag::factory()->count(3)->create(['store_id' => Store::factory()->create()->id]);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->getJson("/api/v1/merchant/stores/{$store->id}/tags")
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_creating_tags_with_duplicate_translation_slug_does_not_silently_succeed(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $this->grantActiveSubscription($store);
        Sanctum::actingAs($admin, ['*'], 'merchant');

        $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/tags",
            $this->tagPayload(),
        )->assertStatus(201);

        // NOTE: CreateTagRequest::rules() does not apply any uniqueness rule
        // to `translations.*.slug`, but `tag_translations` has a DB-level
        // unique(['locale', 'slug']) constraint (see the
        // create_tag_translations_table migration). Submitting the same
        // locale+slug pair a second time — even for an unrelated tag in a
        // different store — is therefore only caught at the database layer,
        // which ExceptionRegistrar has no explicit case for. If it isn't
        // caught anywhere else either, this second request would surface as
        // an uncaught QueryException (HTTP 500) instead of a clean 422
        // validation error. This assertion only checks that the request
        // does NOT report success; see the final report for the concrete
        // status this returns and why it needs the team's attention.
        $response = $this->postJson(
            "/api/v1/merchant/stores/{$store->id}/tags",
            $this->tagPayload(),
        );

        $response->assertJsonPath('success', false);
    }

    public function test_viewing_a_tag_from_another_store_returns_not_found_even_when_a_global_tag_exists(): void
    {
        [$admin, $store] = $this->makeStoreAdmin();
        $this->grantTagPermissionsToStoreAdminRole();
        $otherStore = Store::factory()->create();
        $foreignTag = Tag::factory()->create(['store_id' => $otherStore->id]);
        $foreignTag->translations()->create(['locale' => 'en', 'name' => 'Foreign', 'slug' => 'foreign-tag']);
        // A global tag also exists in the system alongside the foreign one.
        $globalTag = Tag::factory()->create(['store_id' => null]);
        $globalTag->translations()->create(['locale' => 'en', 'name' => 'Global', 'slug' => 'global-tag-2']);

        Sanctum::actingAs($admin, ['*'], 'merchant');

        // NOTE: AdminTagRepository::findInStore() builds its query as
        // scopedQuery()->where('id', $tagId)->orWhereNull('store_id')->first().
        // Because `orWhereNull` is not grouped, normal SQL operator
        // precedence turns this into
        // `(store_id = ? AND id = ?) OR store_id IS NULL`, not the intended
        // `store_id = ? AND (id = ? OR store_id IS NULL)`. Whenever any
        // global tag exists, `->first()` can return that unrelated global
        // tag instead of correctly reporting the requested id as
        // inaccessible. If this test fails, it is very likely confirming
        // that bug rather than a mistake in the test — see the final report.
        $this->getJson("/api/v1/merchant/stores/{$store->id}/tags/{$foreignTag->id}")
            ->assertStatus(404);
    }
}
