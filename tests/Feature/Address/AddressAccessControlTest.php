<?php

declare(strict_types=1);

namespace Tests\Feature\Address;

use App\Models\Address;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Extends the existing (partial) Address coverage in
 * tests/Feature/Checkout/StorefrontAddressSettingsTest.php and
 * EnhancedCheckoutAddressPersistenceTest.php.
 *
 * Focus here: ownership isolation between customers, validation, and the
 * default-address reassignment behavior on delete/update, all through the
 * real /api/v1/storefront/stores/{store}/addresses routes.
 */
class AddressAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $this->customer = User::factory()->customer()->verified()->create();
    }

    private function actingAsCustomer(User $user): static
    {
        Sanctum::actingAs($user, ['*'], 'customer');

        return $this;
    }

    private function url(string $suffix = ''): string
    {
        return "/api/v1/storefront/stores/{$this->store->id}/addresses{$suffix}";
    }

    private function validAddressPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'shipping',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main Street',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
        ], $overrides);
    }

    private function createAddressForUser(User $user, array $overrides = []): Address
    {
        return Address::query()->create(array_merge([
            'store_id' => $this->store->id,
            'user_id' => $user->id,
            'type' => 'shipping',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main Street',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ], $overrides));
    }

    // ── Auth ─────────────────────────────────────────────────────

    public function test_guest_cannot_list_addresses(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
    }

    // ── Happy path ───────────────────────────────────────────────

    public function test_customer_can_create_an_address(): void
    {
        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->url(), $this->validAddressPayload());

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('addresses', [
            'user_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'city' => 'Austin',
        ]);
    }

    public function test_customer_can_list_only_their_own_addresses(): void
    {
        $this->createAddressForUser($this->customer);
        $otherCustomer = User::factory()->customer()->verified()->create();
        $this->createAddressForUser($otherCustomer);

        $response = $this->actingAsCustomer($this->customer)->getJson($this->url());

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    // ── Cross-user isolation (403) ───────────────────────────────

    public function test_customer_cannot_update_another_customers_address(): void
    {
        $victim = User::factory()->customer()->verified()->create();
        $address = $this->createAddressForUser($victim);

        $response = $this->actingAsCustomer($this->customer)
            ->putJson($this->url("/{$address->id}"), $this->validAddressPayload());

        $response->assertStatus(403);
    }

    public function test_customer_cannot_delete_another_customers_address(): void
    {
        $victim = User::factory()->customer()->verified()->create();
        $address = $this->createAddressForUser($victim);

        $response = $this->actingAsCustomer($this->customer)
            ->deleteJson($this->url("/{$address->id}"));

        $response->assertStatus(403);
        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'deleted_at' => null]);
    }

    public function test_customer_cannot_set_another_customers_address_as_default(): void
    {
        $victim = User::factory()->customer()->verified()->create();
        $address = $this->createAddressForUser($victim);

        $response = $this->actingAsCustomer($this->customer)
            ->patchJson($this->url("/{$address->id}/default"));

        $response->assertStatus(403);
    }

    // ── Validation (422) ─────────────────────────────────────────

    public function test_create_address_requires_type(): void
    {
        $payload = $this->validAddressPayload();
        unset($payload['type']);

        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->url(), $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    public function test_create_address_rejects_invalid_type(): void
    {
        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->url(), $this->validAddressPayload(['type' => 'not-a-real-type']));

        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    public function test_create_address_fails_when_required_store_field_is_missing(): void
    {
        // city is one of the store's default required_fields (see
        // StoreAddressSetting::getDefaultRequiredFields()).
        $payload = $this->validAddressPayload();
        unset($payload['city']);

        $response = $this->actingAsCustomer($this->customer)
            ->postJson($this->url(), $payload);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    // ── Edge cases ───────────────────────────────────────────────

    public function test_deleting_the_default_shipping_address_promotes_another_to_default(): void
    {
        $default = $this->createAddressForUser($this->customer, ['is_default_shipping' => true]);
        $other = $this->createAddressForUser($this->customer, ['is_default_shipping' => false]);

        $this->actingAsCustomer($this->customer)
            ->deleteJson($this->url("/{$default->id}"))
            ->assertOk();

        $this->assertTrue($other->fresh()->is_default_shipping);
    }

    public function test_setting_a_new_default_unsets_the_previous_default(): void
    {
        $current = $this->createAddressForUser($this->customer, [
            'type' => 'shipping',
            'is_default_shipping' => true,
        ]);
        $incoming = $this->createAddressForUser($this->customer, [
            'type' => 'shipping',
            'is_default_shipping' => false,
        ]);

        $this->actingAsCustomer($this->customer)
            ->patchJson($this->url("/{$incoming->id}/default"))
            ->assertOk();

        $this->assertFalse($current->fresh()->is_default_shipping);
        $this->assertTrue($incoming->fresh()->is_default_shipping);
    }

    /**
     * Documents real behavior: AddressController::update() only authorizes
     * via AddressPolicy (checks $user->id === $address->user_id) and never
     * checks that the address belongs to the {store} in the URL. A customer
     * can therefore address their OWN address record while impersonating a
     * different store id in the path; the store is only used to look up
     * validation rules, not to scope the address itself.
     */
    public function test_own_address_can_be_updated_through_a_different_stores_url(): void
    {
        $address = $this->createAddressForUser($this->customer);
        $otherStore = Store::factory()->create();

        $response = $this->actingAsCustomer($this->customer)
            ->putJson(
                "/api/v1/storefront/stores/{$otherStore->id}/addresses/{$address->id}",
                $this->validAddressPayload(['city' => 'Dallas'])
            );

        $response->assertOk();
        $this->assertSame('Dallas', $address->fresh()->city);
    }
}
