<?php

namespace Tests\Security;

use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Subscription Middleware Test
 * 
 * Tests that subscription.active middleware blocks write operations
 * when entitlement_status does not grant write access.
 */
class SubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_blocks_write_with_none_status(): void
    {
        // Disable observers to avoid SQLite compatibility issues
        Store::unsetEventDispatcher();
        
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        // Create billing account
        $billingAccount = \App\Models\BillingAccount::create([
            'owner_user_id' => $user->id,
            'billing_email' => $user->email,
            'status' => 'active',
            'trial_used' => false,
            'stores_count' => 1,
            'stores_max' => 1,
        ]);
        
        $store = Store::factory()->create(['owner_id' => $user->id]);
        
        $user->stores()->attach($store->id, ['role' => 'owner']);
        $user->update(['last_active_store_id' => $store->id]);
        
        // Create snapshot with NONE status (no write access)
        StoreEntitlementSnapshot::create([
            'store_id' => $store->id,
            'billing_account_id' => $billingAccount->id,
            'entitlement_status' => EntitlementStatusEnum::NONE,
            'features' => ['products.max' => 1000],
            'products_count' => 0,
            'refreshed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/merchant/stores/{$store->id}/products", [
            'name' => ['en' => 'Test Product'],
            'slug' => 'test-product',
            'description' => ['en' => 'Test description'],
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $response->assertStatus(402)
            ->assertJson([
                'status' => false,
                'error_code' => 'SUBSCRIPTION_REQUIRED',
            ]);
    }

    public function test_middleware_allows_read_with_none_status(): void
    {
        Store::unsetEventDispatcher();
        
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        // Create billing account
        $billingAccount = \App\Models\BillingAccount::create([
            'owner_user_id' => $user->id,
            'billing_email' => $user->email,
            'status' => 'active',
            'trial_used' => false,
            'stores_count' => 1,
            'stores_max' => 1,
        ]);
        
        $store = Store::factory()->create(['owner_id' => $user->id]);
        
        $user->stores()->attach($store->id, ['role' => 'owner']);
        $user->update(['last_active_store_id' => $store->id]);
        
        // Create snapshot with NONE status
        StoreEntitlementSnapshot::create([
            'store_id' => $store->id,
            'billing_account_id' => $billingAccount->id,
            'entitlement_status' => EntitlementStatusEnum::NONE,
            'features' => ['products.max' => 1000],
            'products_count' => 0,
            'refreshed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/merchant/stores/{$store->id}/products");

        // Should allow read operations even with NONE status
        $response->assertSuccessful();
    }

    public function test_middleware_allows_write_with_trial_status(): void
    {
        Store::unsetEventDispatcher();
        
        $user = User::factory()->create(['email_verified_at' => now()]);
        $store = Store::factory()->create(['owner_id' => $user->id]);
        
        $user->stores()->attach($store->id, ['role' => 'owner']);
        $user->update(['last_active_store_id' => $store->id]);
        
        // Create snapshot with TRIAL status (write access granted)
        StoreEntitlementSnapshot::create([
            'store_id' => $store->id,
            'billing_account_id' => 1,
            'entitlement_status' => EntitlementStatusEnum::TRIAL,
            'features' => ['products.max' => 1000],
            'products_count' => 0,
            'refreshed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/merchant/stores/{$store->id}/products", [
            'name' => ['en' => 'Test Product'],
            'slug' => 'test-product',
            'description' => ['en' => 'Test description'],
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        // Should succeed with 201 or validation error, not 402
        $this->assertNotEquals(402, $response->status());
    }

    public function test_middleware_allows_write_with_entitled_status(): void
    {
        Store::unsetEventDispatcher();
        
        $user = User::factory()->create(['email_verified_at' => now()]);
        $store = Store::factory()->create(['owner_id' => $user->id]);
        
        $user->stores()->attach($store->id, ['role' => 'owner']);
        $user->update(['last_active_store_id' => $store->id]);
        
        // Create snapshot with ENTITLED status (write access granted)
        StoreEntitlementSnapshot::create([
            'store_id' => $store->id,
            'billing_account_id' => 1,
            'entitlement_status' => EntitlementStatusEnum::ENTITLED,
            'features' => ['products.max' => 1000],
            'products_count' => 0,
            'refreshed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/merchant/stores/{$store->id}/products", [
            'name' => ['en' => 'Test Product'],
            'slug' => 'test-product',
            'description' => ['en' => 'Test description'],
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        // Should NOT be 402
        $this->assertNotEquals(402, $response->status());
    }

    public function test_middleware_blocks_write_with_read_only_status(): void
    {
        Store::unsetEventDispatcher();
        
        $user = User::factory()->create(['email_verified_at' => now()]);
        $store = Store::factory()->create(['owner_id' => $user->id]);
        
        $user->stores()->attach($store->id, ['role' => 'owner']);
        $user->update(['last_active_store_id' => $store->id]);
        
        // Create snapshot with READ_ONLY status (no write access)
        StoreEntitlementSnapshot::create([
            'store_id' => $store->id,
            'billing_account_id' => 1,
            'entitlement_status' => EntitlementStatusEnum::READ_ONLY,
            'features' => ['products.max' => 1000],
            'products_count' => 0,
            'refreshed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/merchant/stores/{$store->id}/products", [
            'name' => ['en' => 'Test Product'],
            'slug' => 'test-product',
            'description' => ['en' => 'Test description'],
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $response->assertStatus(402)
            ->assertJson([
                'status' => false,
                'error_code' => 'SUBSCRIPTION_REQUIRED',
            ]);
    }
}
