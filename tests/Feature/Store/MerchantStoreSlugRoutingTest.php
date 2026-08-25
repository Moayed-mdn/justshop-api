<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MerchantStoreSlugRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Store::unsetEventDispatcher();

        $this->merchant = User::factory()->merchant()->verified()->create();
        $this->merchant->assignRole(RoleEnum::STORE_ADMIN->value);
        $this->store = Store::factory()->create([
            'owner_id' => $this->merchant->id,
            'slug' => 'alpha-store',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $this->merchant->stores()->attach($this->store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $this->merchant = $this->merchant->fresh();
        Sanctum::actingAs($this->merchant, ['*'], 'merchant');
    }

    public function test_store_detail_endpoint_accepts_slug_route_binding(): void
    {
        $response = $this->getJson("/api/v1/merchant/stores/{$this->store->slug}");

        $response->assertOk()
            ->assertJsonPath('data.id', $this->store->id)
            ->assertJsonPath('data.slug', $this->store->slug);
    }

    public function test_shipping_address_settings_endpoint_accepts_only_slug(): void
    {
        $slugResponse = $this->getJson("/api/v1/merchant/stores/{$this->store->slug}/shipping/address-settings");
        $idResponse = $this->getJson("/api/v1/merchant/stores/{$this->store->id}/shipping/address-settings");

        $slugResponse->assertOk()
            ->assertJsonPath('data.store_id', $this->store->id);

        $idResponse->assertNotFound();
    }

    public function test_merchant_cannot_view_another_merchants_store_by_slug(): void
    {
        $otherMerchant = User::factory()->merchant()->verified()->create();
        $otherStore = Store::factory()->create([
            'owner_id' => $otherMerchant->id,
            'slug' => 'other-merchants-store',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        // $this->merchant (acting user) has no ownership or membership on $otherStore.
        $response = $this->getJson("/api/v1/merchant/stores/{$otherStore->slug}");

        $response->assertForbidden();
    }

    public function test_creating_store_without_required_fields_fails_validation(): void
    {
        $response = $this->postJson('/api/v1/merchant/stores', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_creating_store_with_slug_already_taken_by_another_merchants_store_fails_validation(): void
    {
        // $this->store already owns the slug 'alpha-store' (created in setUp,
        // owned by $this->merchant). A *different* merchant attempting to
        // create a *new* store re-using that slug must be rejected: slugs are
        // globally unique across stores/owners (CreateStoreRequest's
        // 'unique:stores,slug' rule), not merely unique per-owner.
        $anotherMerchant = User::factory()->merchant()->verified()->create();
        Sanctum::actingAs($anotherMerchant, ['*'], 'merchant');

        $response = $this->postJson('/api/v1/merchant/stores', [
            'name' => 'Conflicting Store Name',
            'slug' => 'alpha-store',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);

        $this->assertSame(
            1,
            Store::where('slug', 'alpha-store')->count(),
            'no second store should have been created with the conflicting slug'
        );
    }

    public function test_creating_store_with_invalid_slug_format_fails_validation(): void
    {
        // CreateStoreRequest enforces regex:/^[a-z0-9-]+$/ — uppercase and
        // spaces are not a valid slug shape.
        $response = $this->postJson('/api/v1/merchant/stores', [
            'name' => 'My New Store',
            'slug' => 'My New Store!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_creating_store_with_reserved_slug_fails_validation(): void
    {
        // ReservedOrBlockedSlug rule blocks well-known reserved paths like
        // 'admin' from being claimed as a store slug.
        $response = $this->postJson('/api/v1/merchant/stores', [
            'name' => 'Admin Store',
            'slug' => 'admin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_two_different_merchants_can_create_stores_with_different_slugs(): void
    {
        $anotherMerchant = User::factory()->merchant()->verified()->create();
        Sanctum::actingAs($anotherMerchant, ['*'], 'merchant');

        $response = $this->postJson('/api/v1/merchant/stores', [
            'name' => 'Beta Store',
            'slug' => 'beta-store',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'beta-store');

        $this->assertDatabaseHas('stores', [
            'slug' => 'beta-store',
            'owner_id' => $anotherMerchant->id,
        ]);
    }
}
