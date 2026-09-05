<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Actions\Store\OnboardMerchantToStripeAction;
use App\DTOs\Store\OnboardMerchantToStripeDTO;
use App\Enums\Billing\BillingAccountStatusEnum;
use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Models\BillingAccount;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Stripe\Account;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeConnectOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.stripe.connect_return_base_url', 'http://localhost:3000');

        $this->owner = User::factory()->merchant()->verified()->create([
            'email' => 'merchant@example.com',
        ]);

        $this->store = Store::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);
    }

    /**
     * The POST /onboard HTTP route is gated by the `subscription.active`
     * middleware (EnsureActiveSubscription -> FeatureGateService), which
     * requires a StoreEntitlementSnapshot granting write access.
     */
    private function grantActiveEntitlement(Store $store): StoreEntitlementSnapshot
    {
        $billingAccount = BillingAccount::create([
            'owner_user_id' => $store->owner_id,
            'billing_email' => 'billing+'.$store->owner_id.'@example.test',
            'legal_name' => 'Test Billing Account',
            'country_code' => 'US',
            'default_currency' => 'USD',
            'status' => BillingAccountStatusEnum::ACTIVE,
            'trial_used' => false,
            'stores_count' => 1,
            'stores_max' => null,
            'metadata' => [],
        ]);

        return StoreEntitlementSnapshot::create([
            'store_id' => $store->id,
            'billing_account_id' => $billingAccount->id,
            'entitlement_status' => EntitlementStatusEnum::ENTITLED,
            'features' => [],
            'products_count' => 0,
            'refreshed_at' => now(),
        ]);
    }

    private function mockStripeAccountAndLinkCreation(string $accountId = 'acct_http_onboard'): void
    {
        $mockStripe = Mockery::mock(StripeClient::class);
        $mockAccountsService = Mockery::mock();
        $mockAccountLinksService = Mockery::mock();

        $mockStripe->accounts = $mockAccountsService;
        $mockStripe->accountLinks = $mockAccountLinksService;

        $mockAccountsService
            ->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'id' => $accountId,
                'type' => 'express',
                'email' => $this->owner->email,
            ]);

        $mockAccountLinksService
            ->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'url' => 'https://connect.stripe.com/setup/http_onboard_url',
            ]);

        $this->app->instance(StripeClient::class, $mockStripe);
    }

    public function test_creates_stripe_connect_account_for_new_merchant(): void
    {
        $mockStripe = Mockery::mock(StripeClient::class);
        $mockAccountsService = Mockery::mock();
        $mockAccountLinksService = Mockery::mock();

        $mockStripe->accounts = $mockAccountsService;
        $mockStripe->accountLinks = $mockAccountLinksService;

        $capturedAccountParams = null;
        $mockAccountsService
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($params) use (&$capturedAccountParams) {
                $capturedAccountParams = $params;

                return true;
            })
            ->andReturn((object) [
                'id' => 'acct_test_new_merchant',
                'type' => 'express',
                'email' => 'merchant@example.com',
            ]);

        $mockAccountLinksService
            ->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'url' => 'https://connect.stripe.com/setup/test_onboarding_url',
            ]);

        $this->app->instance(StripeClient::class, $mockStripe);

        $action = $this->app->make(OnboardMerchantToStripeAction::class);
        $onboardingUrl = $action->execute(new OnboardMerchantToStripeDTO($this->store->id));

        $this->assertEquals('https://connect.stripe.com/setup/test_onboarding_url', $onboardingUrl);

        $this->store->refresh();
        $this->assertEquals('acct_test_new_merchant', $this->store->stripe_account_id);
        $this->assertEquals('express', $this->store->stripe_account_type);

        // Verify account creation parameters
        $this->assertNotNull($capturedAccountParams);
        $this->assertEquals('express', $capturedAccountParams['type']);
        $this->assertEquals('merchant@example.com', $capturedAccountParams['email']);
        $this->assertEquals((string) $this->store->id, $capturedAccountParams['metadata']['store_id']);
    }

    public function test_reuses_existing_stripe_connect_account(): void
    {
        $this->store->update([
            'stripe_account_id' => 'acct_existing_merchant',
            'stripe_account_type' => 'express',
        ]);

        $mockStripe = Mockery::mock(StripeClient::class);
        $mockAccountsService = Mockery::mock();
        $mockAccountLinksService = Mockery::mock();

        $mockStripe->accounts = $mockAccountsService;
        $mockStripe->accountLinks = $mockAccountLinksService;

        // Should NOT create a new account
        $mockAccountsService->shouldNotReceive('create');

        // Should create a new onboarding link
        $mockAccountLinksService
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['account'] === 'acct_existing_merchant'
                    && $params['type'] === 'account_onboarding';
            }))
            ->andReturn((object) [
                'url' => 'https://connect.stripe.com/setup/existing_onboarding_url',
            ]);

        $this->app->instance(StripeClient::class, $mockStripe);

        $action = $this->app->make(OnboardMerchantToStripeAction::class);
        $onboardingUrl = $action->execute(new OnboardMerchantToStripeDTO($this->store->id));

        $this->assertEquals('https://connect.stripe.com/setup/existing_onboarding_url', $onboardingUrl);

        // Stripe account ID should remain unchanged
        $this->store->refresh();
        $this->assertEquals('acct_existing_merchant', $this->store->stripe_account_id);
    }

    public function test_onboarding_link_includes_correct_redirect_urls(): void
    {
        $mockStripe = Mockery::mock(StripeClient::class);
        $mockAccountsService = Mockery::mock();
        $mockAccountLinksService = Mockery::mock();

        $mockStripe->accounts = $mockAccountsService;
        $mockStripe->accountLinks = $mockAccountLinksService;

        $mockAccountsService
            ->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'id' => 'acct_test_urls',
                'type' => 'express',
            ]);

        $capturedLinkParams = null;
        $mockAccountLinksService
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($params) use (&$capturedLinkParams) {
                $capturedLinkParams = $params;

                return true;
            })
            ->andReturn((object) [
                'url' => 'https://connect.stripe.com/setup/test_url',
            ]);

        $this->app->instance(StripeClient::class, $mockStripe);

        $action = $this->app->make(OnboardMerchantToStripeAction::class);
        $action->execute(new OnboardMerchantToStripeDTO($this->store->id));

        $this->assertNotNull($capturedLinkParams);
        $this->assertEquals('account_onboarding', $capturedLinkParams['type']);
        $this->assertEquals(
            'http://localhost:3000/merchant/settings/payments/stripe/onboard',
            $capturedLinkParams['refresh_url']
        );
        $this->assertEquals(
            'http://localhost:3000/merchant/settings/payments/stripe/success',
            $capturedLinkParams['return_url']
        );
    }

    public function test_merchant_can_check_stripe_connect_status(): void
    {
        $this->store->update([
            'stripe_account_id' => 'acct_status_check',
            'stripe_account_type' => 'express',
            'stripe_details_submitted' => true,
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => true,
            'stripe_onboarded_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/merchant/stores/{$this->store->slug}/stripe-connect/status");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'stripe_account_id' => 'acct_status_check',
                'stripe_account_type' => 'express',
                'details_submitted' => true,
                'charges_enabled' => true,
                'payouts_enabled' => true,
                'can_receive_payments' => true,
            ],
        ]);
    }

    public function test_store_helper_methods_work_correctly(): void
    {
        // No Stripe account
        $this->assertFalse($this->store->hasStripeAccount());
        $this->assertFalse($this->store->canReceivePayments());

        // Has account but charges not enabled
        $this->store->update([
            'stripe_account_id' => 'acct_partial',
            'stripe_details_submitted' => true,
            'stripe_charges_enabled' => false,
            'stripe_payouts_enabled' => false,
        ]);

        $this->assertTrue($this->store->hasStripeAccount());
        $this->assertFalse($this->store->canReceivePayments());

        // Charges enabled (can receive payments even if payouts pending)
        $this->store->update([
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => false,
        ]);

        $this->assertTrue($this->store->hasStripeAccount());
        $this->assertTrue($this->store->canReceivePayments());

        // Fully onboarded (both charges and payouts)
        $this->store->update([
            'stripe_payouts_enabled' => true,
        ]);

        $this->assertTrue($this->store->hasStripeAccount());
        $this->assertTrue($this->store->canReceivePayments());
    }

    public function test_onboard_endpoint_creates_stripe_connect_account_via_http(): void
    {
        $this->grantActiveEntitlement($this->store);
        $this->mockStripeAccountAndLinkCreation();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/merchant/stores/{$this->store->slug}/stripe-connect/onboard");

        $response->assertOk()
            ->assertJsonPath('data.onboarding_url', 'https://connect.stripe.com/setup/http_onboard_url')
            ->assertJsonPath('data.stripe_account_id', 'acct_http_onboard');

        $this->store->refresh();
        $this->assertSame('acct_http_onboard', $this->store->stripe_account_id);
    }

    public function test_onboard_endpoint_requires_an_active_subscription(): void
    {
        // Deliberately no StoreEntitlementSnapshot granted for this store.
        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/merchant/stores/{$this->store->slug}/stripe-connect/onboard");

        $response->assertStatus(402);
        $this->store->refresh();
        $this->assertNull($this->store->stripe_account_id);
    }

    public function test_guest_cannot_check_stripe_connect_status(): void
    {
        $response = $this->getJson("/api/v1/merchant/stores/{$this->store->slug}/stripe-connect/status");

        $response->assertUnauthorized();
    }

    public function test_merchant_cannot_check_another_stores_stripe_connect_status(): void
    {
        $otherMerchant = User::factory()->merchant()->verified()->create();

        // $otherMerchant has no ownership or membership on $this->store.
        $response = $this->actingAs($otherMerchant)
            ->getJson("/api/v1/merchant/stores/{$this->store->slug}/stripe-connect/status");

        $response->assertForbidden();
    }

    public function test_status_endpoint_reconciles_stale_local_state_directly_from_stripe(): void
    {
        // Local state still looks incomplete (details_submitted false), so
        // ReconcileStripeConnectStatusAction should fetch the account from
        // Stripe and self-heal the local columns — covering the "webhook
        // never arrived" fallback path, which the existing fully-synced
        // fixture in test_merchant_can_check_stripe_connect_status never
        // exercises (isAlreadyFullySynced() short-circuits it).
        $this->store->update([
            'stripe_account_id' => 'acct_stale',
            'stripe_account_type' => 'express',
            'stripe_details_submitted' => false,
            'stripe_charges_enabled' => false,
            'stripe_payouts_enabled' => false,
        ]);

        $mockStripe = Mockery::mock(StripeClient::class);
        $mockAccountsService = Mockery::mock();
        $mockStripe->accounts = $mockAccountsService;

        $mockAccountsService
            ->shouldReceive('retrieve')
            ->once()
            ->with('acct_stale')
            ->andReturn(Account::constructFrom([
                'id' => 'acct_stale',
                'details_submitted' => true,
                'charges_enabled' => true,
                'payouts_enabled' => true,
            ]));

        $this->app->instance(StripeClient::class, $mockStripe);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/merchant/stores/{$this->store->slug}/stripe-connect/status");

        $response->assertOk()
            ->assertJsonPath('data.details_submitted', true)
            ->assertJsonPath('data.charges_enabled', true)
            ->assertJsonPath('data.payouts_enabled', true)
            ->assertJsonPath('data.can_receive_payments', true);

        $this->store->refresh();
        $this->assertTrue($this->store->stripe_details_submitted);
        $this->assertNotNull($this->store->stripe_onboarded_at);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
