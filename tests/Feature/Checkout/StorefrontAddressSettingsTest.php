<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Enums\ErrorCode;
use App\Models\Address;
use App\Models\Store;
use App\Models\StoreAddressSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StorefrontAddressSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_legacy_hosted_checkout_initiation_route_is_not_available(): void
    {
        $store = Store::factory()->create();

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/checkout", [
            'items' => [
                ['product_variant_id' => 1, 'quantity' => 1],
            ],
        ]);

        $response->assertNotFound();
    }

    public function test_legacy_hosted_checkout_status_route_is_not_available(): void
    {
        $response = $this->getJson('/api/v1/storefront/checkout/status/cs_test_legacy');

        $response->assertNotFound();
    }

    public function test_storefront_address_creation_uses_merchant_validation_rules(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->customer()->verified()->create();

        StoreAddressSetting::query()->create([
            'store_id' => $store->id,
            'allowed_countries' => ['US'],
            'required_fields' => ['first_name', 'last_name', 'address_line_1', 'city', 'state', 'postal_code', 'country'],
            'validation_rules' => [],
            'require_phone' => true,
            'require_company' => false,
            'allow_po_boxes' => true,
        ]);

        Sanctum::actingAs($user, ['*'], 'customer');

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/addresses", [
            'type' => 'shipping',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main Street',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', ErrorCode::VAL_001->value)
            ->assertJsonPath('errors.phone.0', 'Phone number is required.');
    }

    public function test_creating_default_shipping_address_persists_shipping_default_flags(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->customer()->verified()->create();

        Sanctum::actingAs($user, ['*'], 'customer');

        $response = $this->postJson("/api/v1/storefront/stores/{$store->id}/addresses", [
            'type' => 'shipping',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main Street',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
            'phone' => '+1-555-0000',
            'is_default' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.is_default_shipping', true)
            ->assertJsonPath('data.is_default_billing', false);

        $addressId = (int) $response->json('data.id');

        $this->assertDatabaseHas('addresses', [
            'id' => $addressId,
            'store_id' => $store->id,
            'user_id' => $user->id,
            'is_default_shipping' => true,
            'is_default_billing' => false,
        ]);
    }

    public function test_setting_default_address_reassigns_shipping_default_flag(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->customer()->verified()->create();

        $firstAddress = Address::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'shipping',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main Street',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
            'phone' => '+1-555-0000',
            'is_default_shipping' => true,
            'is_default_billing' => false,
        ]);

        $secondAddress = Address::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'shipping',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '456 Oak Avenue',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78702',
            'country' => 'US',
            'phone' => '+1-555-1111',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);

        Sanctum::actingAs($user, ['*'], 'customer');

        $response = $this->patchJson(
            "/api/v1/storefront/stores/{$store->id}/addresses/{$secondAddress->id}/default"
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $firstAddress->refresh();
        $secondAddress->refresh();

        $this->assertFalse($firstAddress->is_default_shipping);
        $this->assertTrue($secondAddress->is_default_shipping);
        $this->assertFalse($secondAddress->is_default_billing);
    }
}
