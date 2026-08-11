<?php

namespace Tests\Security;

use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\BillingAccount;
use App\Models\Plan;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Subscription Enforcement Test
 * 
 * Tests that write operations are blocked when subscription is expired/inactive
 * while read operations remain accessible (read-only mode).
 * 
 * @group security
 * @group subscription
 */
class SubscriptionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Store $store;
    private BillingAccount $billingAccount;
    private Subscription $subscription;
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create plan directly
        $this->plan = Plan::create([
            'code' => 'starter',
            'name' => ['en' => 'Starter'],
            'description' => ['en' => 'Starter plan'],
            'tier' => 'starter',
            'is_public' => true,
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 1,
        ]);

        // Create user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        // Create billing account
        $this->billingAccount = BillingAccount::create([
            'owner_user_id' => $this->user->id,
            'billing_email' => $this->user->email,
            'status' => 'active',
            'trial_used' => false,
            'stores_count' => 1,
            'stores_max' => 1,
        ]);

        // Create store without factory (avoid billing_account_id issue)
        $this->store = new Store([
            'owner_id' => $this->user->id,
            'name' => 'Test Store',
            'slug' => 'test-store',
            'status' => 'active',
            'is_active' => true,
            'currency' => 'USD',
            'timezone' => 'UTC',
        ]);
        $this->store->id = 1; // Mock ID
        $this->store->save();

        // Link user to store
        $this->user->update(['last_active_store_id' => $this->store->id]);
        $this->user->stores()->attach($this->store->id);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function expired_subscription_blocks_product_creation(): void
    {
        $this->createExpiredSubscription();

        $response = $this->postJson("/api/v1/merchant/stores/{$this->store->id}/products", [
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
                'meta' => [
                    'subscription_required' => true,
                ],
            ]);
    }

    /** @test */
    public function expired_subscription_allows_product_listing(): void
    {
        $this->createExpiredSubscription();

        $response = $this->getJson("/api/v1/merchant/stores/{$this->store->id}/products");

        $response->assertStatus(200);
    }

    /** @test */
    public function expired_subscription_blocks_product_update(): void
    {
        // Create product with active subscription first
        $this->createActiveSubscription();
        
        $product = \App\Models\Product::factory()->create([
            'store_id' => $this->store->id,
            'name' => ['en' => 'Original Name'],
            'slug' => 'original-slug',
        ]);

        // Expire subscription
        $this->expireSubscription();

        $response = $this->patchJson("/api/v1/merchant/stores/{$this->store->id}/products/{$product->id}", [
            'name' => ['en' => 'Updated Name'],
        ]);

        $response->assertStatus(402);
    }

    /** @test */
    public function expired_subscription_blocks_category_creation(): void
    {
        $this->createExpiredSubscription();

        $response = $this->postJson("/api/v1/merchant/stores/{$this->store->id}/categories", [
            'name' => ['en' => 'Test Category'],
            'slug' => 'test-category',
        ]);

        $response->assertStatus(402);
    }

    /** @test */
    public function expired_subscription_blocks_order_status_update(): void
    {
        // Create order with active subscription first
        $this->createActiveSubscription();
        
        $order = \App\Models\Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'pending',
        ]);

        // Expire subscription
        $this->expireSubscription();

        $response = $this->patchJson("/api/v1/merchant/stores/{$this->store->id}/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertStatus(402);
    }

    /** @test */
    public function expired_subscription_allows_order_viewing(): void
    {
        $this->createExpiredSubscription();

        $response = $this->getJson("/api/v1/merchant/stores/{$this->store->id}/orders");

        $response->assertStatus(200);
    }

    /** @test */
    public function expired_subscription_blocks_media_upload(): void
    {
        $this->createExpiredSubscription();

        $response = $this->postJson("/api/v1/merchant/stores/{$this->store->id}/media/upload", [
            'file' => 'fake-file-data',
        ]);

        $response->assertStatus(402);
    }

    /** @test */
    public function expired_subscription_allows_billing_page_access(): void
    {
        $this->createExpiredSubscription();

        $response = $this->getJson('/api/v1/merchant/billing/subscription');

        // Should allow access to billing endpoints to renew subscription
        $response->assertSuccessful();
    }

    /** @test */
    public function active_subscription_allows_all_operations(): void
    {
        $this->createActiveSubscription();

        // Test product creation
        $response = $this->postJson("/api/v1/merchant/stores/{$this->store->id}/products", [
            'name' => ['en' => 'Test Product'],
            'slug' => 'test-product',
            'description' => ['en' => 'Test description'],
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $response->assertStatus(201);

        // Test category creation
        $response = $this->postJson("/api/v1/merchant/stores/{$this->store->id}/categories", [
            'name' => ['en' => 'Test Category'],
            'slug' => 'test-category',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function trialing_subscription_allows_write_operations(): void
    {
        $this->createTrialingSubscription();

        $response = $this->postJson("/api/v1/merchant/stores/{$this->store->id}/products", [
            'name' => ['en' => 'Test Product'],
            'slug' => 'test-product',
            'description' => ['en' => 'Test description'],
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function past_due_subscription_blocks_write_operations(): void
    {
        $this->createPastDueSubscription();

        $response = $this->postJson("/api/v1/merchant/stores/{$this->store->id}/products", [
            'name' => ['en' => 'Test Product'],
            'slug' => 'test-product',
            'description' => ['en' => 'Test description'],
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $response->assertStatus(402);
    }

    private function createExpiredSubscription(): void
    {
        $this->subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatusEnum::EXPIRED->value,
            'billing_cycle' => 'monthly',
            'provider' => 'stripe',
            'trial_starts_at' => now()->subDays(20),
            'trial_ends_at' => now()->subDays(5),
            'current_period_starts_at' => now()->subDays(20),
            'current_period_ends_at' => now()->subDays(5),
            'ended_at' => now()->subDays(1),
        ]);

        $this->createEntitlementSnapshot(EntitlementStatusEnum::NONE);
    }

    private function createActiveSubscription(): void
    {
        $this->subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatusEnum::ACTIVE->value,
            'billing_cycle' => 'monthly',
            'provider' => 'stripe',
            'current_period_starts_at' => now()->subDays(1),
            'current_period_ends_at' => now()->addDays(29),
        ]);

        $this->createEntitlementSnapshot(EntitlementStatusEnum::ENTITLED);
    }

    private function createTrialingSubscription(): void
    {
        $this->subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatusEnum::TRIALING->value,
            'billing_cycle' => 'monthly',
            'provider' => 'stripe',
            'trial_starts_at' => now()->subDays(1),
            'trial_ends_at' => now()->addDays(13),
            'current_period_starts_at' => now()->subDays(1),
            'current_period_ends_at' => now()->addDays(13),
        ]);

        $this->createEntitlementSnapshot(EntitlementStatusEnum::TRIAL);
    }

    private function createPastDueSubscription(): void
    {
        $this->subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatusEnum::PAST_DUE->value,
            'billing_cycle' => 'monthly',
            'provider' => 'stripe',
            'current_period_starts_at' => now()->subDays(5),
            'current_period_ends_at' => now()->addDays(25),
            'grace_period_ends_at' => now()->addDays(3),
        ]);

        $this->createEntitlementSnapshot(EntitlementStatusEnum::READ_ONLY);
    }

    private function expireSubscription(): void
    {
        $this->subscription->update([
            'status' => SubscriptionStatusEnum::EXPIRED->value,
            'ended_at' => now(),
        ]);

        $this->createEntitlementSnapshot(EntitlementStatusEnum::NONE);
    }

    private function createEntitlementSnapshot(EntitlementStatusEnum $status): void
    {
        StoreEntitlementSnapshot::updateOrCreate(
            [
                'store_id' => $this->store->id,
            ],
            [
                'billing_account_id' => $this->billingAccount->id,
                'entitlement_status' => $status,
                'features' => [
                    'products.max' => 1000,
                    'users.max' => 2,
                    'analytics.advanced' => false,
                ],
                'products_count' => 0,
                'refreshed_at' => now(),
            ]
        );
    }
}
