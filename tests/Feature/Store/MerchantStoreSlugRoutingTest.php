<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MerchantStoreSlugRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = User::factory()->merchant()->verified()->create();
        $this->store = Store::factory()->create([
            'owner_id' => $this->merchant->id,
            'slug' => 'alpha-store',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $this->merchant->stores()->attach($this->store->id, ['role' => 'owner']);

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
}
