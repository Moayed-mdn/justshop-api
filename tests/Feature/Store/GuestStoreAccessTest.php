<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestStoreAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite (used for tests) lacks the GREATEST() function that
        // StoreObserver relies on when adjusting BillingAccount store counts.
        Store::unsetEventDispatcher();
    }

    public function test_guest_cannot_create_a_store(): void
    {
        $response = $this->postJson('/api/v1/merchant/stores', [
            'name' => 'Guest Attempt Store',
            'slug' => 'guest-attempt-store',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseMissing('stores', ['slug' => 'guest-attempt-store']);
    }

    public function test_guest_cannot_view_store_detail_endpoint(): void
    {
        $store = Store::factory()->create([
            'slug' => 'gamma-store',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/merchant/stores/{$store->slug}");

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_check_slug_availability(): void
    {
        $response = $this->postJson('/api/v1/merchant/stores/validate-slug', [
            'slug' => 'anything',
        ]);

        $response->assertUnauthorized();
    }
}
