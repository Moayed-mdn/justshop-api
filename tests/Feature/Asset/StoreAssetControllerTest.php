<?php

declare(strict_types=1);

namespace Tests\Feature\Asset;

use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Asset\StoreAsset;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * StoreAssetController feature tests.
 *
 * Covers /api/v1/merchant/stores/{store}/assets (index/store/update/destroy).
 */
class StoreAssetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Creates a merchant user attached to a store as store_admin, with the
     * given permissions granted via the real store_admin Spatie role (same
     * pattern used by the existing merchant test suite, e.g.
     * MarketingSectionTypeTest).
     */
    private function merchantWithStoreAndPermissions(array $permissions): array
    {
        $user = User::factory()->merchant()->verified()->create();
        $store = Store::factory()->create(['owner_id' => $user->id]);
        $user->stores()->attach($store->id, ['role' => 'store_admin']);

        $role = Role::firstOrCreate(['name' => RoleEnum::STORE_ADMIN->value, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        $user->assignRole(RoleEnum::STORE_ADMIN->value);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [$user, $store];
    }

    private function grantActiveEntitlement(Store $store): void
    {
        StoreEntitlementSnapshot::query()->create([
            'store_id' => $store->id,
            'entitlement_status' => EntitlementStatusEnum::ENTITLED,
            'features' => [],
            'products_count' => 0,
            'refreshed_at' => now(),
        ]);
    }

    private function asMerchant(User $user): static
    {
        Sanctum::actingAs($user, ['*'], 'merchant');

        return $this;
    }

    private function url(Store $store, string $suffix = ''): string
    {
        return "/api/v1/merchant/stores/{$store->id}/assets{$suffix}";
    }

    // ── Auth ─────────────────────────────────────────────────────

    public function test_guest_cannot_list_assets(): void
    {
        $store = Store::factory()->create();

        $this->getJson($this->url($store))->assertUnauthorized();
    }

    // ── Happy path ───────────────────────────────────────────────

    public function test_merchant_with_permission_can_upload_a_logo_asset(): void
    {
        [$user, $store] = $this->merchantWithStoreAndPermissions([
            PermissionEnum::THEME_CREATE,
        ]);
        $this->grantActiveEntitlement($store);

        $response = $this->asMerchant($user)->post($this->url($store), [
            'name' => 'Store Logo',
            'type' => 'logo',
            'file' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('store_assets', [
            'store_id' => $store->id,
            'name' => 'Store Logo',
            'type' => 'logo',
        ]);
    }

    public function test_merchant_with_permission_can_delete_an_asset(): void
    {
        [$user, $store] = $this->merchantWithStoreAndPermissions([
            PermissionEnum::THEME_DELETE,
        ]);
        $this->grantActiveEntitlement($store);

        $asset = StoreAsset::query()->create([
            'store_id' => $store->id,
            'name' => 'Old Banner',
            'type' => 'banner',
            'file_path' => "stores/{$store->id}/assets/banner/old.png",
            'file_url' => 'http://example.test/old.png',
            'mime_type' => 'image/png',
            'file_size' => 1024,
        ]);

        $response = $this->asMerchant($user)->deleteJson($this->url($store, "/{$asset->id}"));

        $response->assertOk();
        $this->assertSoftDeleted('store_assets', ['id' => $asset->id]);
    }

    // ── Permission (403) ─────────────────────────────────────────

    public function test_merchant_without_permission_cannot_upload_asset(): void
    {
        [$user, $store] = $this->merchantWithStoreAndPermissions([]);
        $this->grantActiveEntitlement($store);

        $response = $this->asMerchant($user)->post($this->url($store), [
            'name' => 'Store Logo',
            'type' => 'logo',
            'file' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertStatus(403);
    }

    public function test_merchant_with_no_store_membership_cannot_list_assets(): void
    {
        $outsider = User::factory()->merchant()->verified()->create();
        $store = Store::factory()->create();

        $response = $this->asMerchant($outsider)->getJson($this->url($store));

        $response->assertStatus(403);
    }

    // ── Validation (422) ─────────────────────────────────────────

    public function test_upload_requires_a_file(): void
    {
        [$user, $store] = $this->merchantWithStoreAndPermissions([
            PermissionEnum::THEME_CREATE,
        ]);
        $this->grantActiveEntitlement($store);

        $response = $this->asMerchant($user)->postJson($this->url($store), [
            'name' => 'Store Logo',
            'type' => 'logo',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_invalid_asset_type(): void
    {
        [$user, $store] = $this->merchantWithStoreAndPermissions([
            PermissionEnum::THEME_CREATE,
        ]);
        $this->grantActiveEntitlement($store);

        $response = $this->asMerchant($user)->post($this->url($store), [
            'name' => 'Store Logo',
            'type' => 'not-a-real-type',
            'file' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    // ── Edge case: MIME type mismatch ─────────────────────────────

    public function test_upload_rejects_a_text_file_declared_as_logo(): void
    {
        [$user, $store] = $this->merchantWithStoreAndPermissions([
            PermissionEnum::THEME_CREATE,
        ]);
        $this->grantActiveEntitlement($store);

        $response = $this->asMerchant($user)->post($this->url($store), [
            'name' => 'Fake Logo',
            'type' => 'logo',
            'file' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
        ]);

        // Caught by UploadAssetAction's MIME check (InvalidAssetTypeException),
        // not by the FormRequest — real behavior is 422 with VAL_001.
        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseMissing('store_assets', ['name' => 'Fake Logo']);
    }

    // ── Subscription gate ──────────────────────────────────────────

    public function test_upload_is_blocked_without_an_active_subscription(): void
    {
        [$user, $store] = $this->merchantWithStoreAndPermissions([
            PermissionEnum::THEME_CREATE,
        ]);
        // Deliberately not calling grantActiveEntitlement() here.

        $response = $this->asMerchant($user)->post($this->url($store), [
            'name' => 'Store Logo',
            'type' => 'logo',
            'file' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertStatus(402);
    }

    public function test_reading_assets_does_not_require_an_active_subscription(): void
    {
        [$user, $store] = $this->merchantWithStoreAndPermissions([
            PermissionEnum::THEME_VIEW,
        ]);
        // No entitlement snapshot created — index is a read op, not gated.

        $response = $this->asMerchant($user)->getJson($this->url($store));

        $response->assertOk();
    }

    // ── KNOWN BUG: cross-tenant asset isolation ────────────────────
    //
    // StoreAssetController::update()/destroy() authorize only against
    // [ThemePolicy::class, $store] (does the user have THEME_UPDATE/DELETE
    // on the store in the URL?). The {asset} route parameter is bound
    // globally (no ->scopeBindings() on this route group, unlike the
    // sibling `themes` group) and neither method checks
    // $asset->store_id === $store->id. A store_admin of Store A can
    // therefore delete or edit an asset belonging to a completely
    // unrelated Store B by passing Store A's id in the URL and Store B's
    // asset id.
    //
    // This test asserts the SECURE behavior (403 / asset untouched) and is
    // expected to FAIL against the current code — see final report.

    public function test_merchant_cannot_delete_an_asset_belonging_to_a_different_store(): void
    {
        [$user, $storeA] = $this->merchantWithStoreAndPermissions([
            PermissionEnum::THEME_DELETE,
        ]);
        $this->grantActiveEntitlement($storeA);

        $storeB = Store::factory()->create();
        $assetOfStoreB = StoreAsset::query()->create([
            'store_id' => $storeB->id,
            'name' => 'Store B Banner',
            'type' => 'banner',
            'file_path' => "stores/{$storeB->id}/assets/banner/b.png",
            'file_url' => 'http://example.test/b.png',
            'mime_type' => 'image/png',
            'file_size' => 2048,
        ]);

        $response = $this->asMerchant($user)
            ->deleteJson($this->url($storeA, "/{$assetOfStoreB->id}"));

        $response->assertStatus(403);
        $this->assertDatabaseHas('store_assets', ['id' => $assetOfStoreB->id, 'deleted_at' => null]);
    }
}
