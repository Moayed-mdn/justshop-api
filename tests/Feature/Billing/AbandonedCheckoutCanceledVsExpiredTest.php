<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Actions\Billing\CreateCheckoutSessionAction;
use App\Actions\Subscription\CancelSubscriptionAction;
use App\Actions\Subscription\StartTrialAction;
use App\Actions\Subscription\UpgradePlanAction;
use App\DTOs\Billing\CreateCheckoutSessionDTO;
use App\DTOs\Subscription\CancelSubscriptionDTO;
use App\DTOs\Subscription\ChangePlanDTO;
use App\DTOs\Subscription\StartTrialDTO;
use App\Enums\Subscription\BillingCycleEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\BillingAccount;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Subscription\SubscriptionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critical regression test for abandoned checkout cancellation bug.
 * 
 * ROOT CAUSE: CreateCheckoutSessionAction used direct ->update(['status' => 'canceled'])
 * on incomplete subscriptions, which:
 * 1. Bypassed SubscriptionStateMachine (INCOMPLETE → CANCELED not in allowedTransitions)
 * 2. Made abandoned checkouts appear in scopeWithAccess() alongside real subscriptions
 * 3. Caused getActiveForAccount()->latest() to return the abandoned checkout (newer ID)
 *    instead of the actual trial/active subscription
 * 
 * FIX: Use state machine to transition INCOMPLETE → EXPIRED (allowed + semantically correct
 * for never-activated checkouts).
 */
class AbandonedCheckoutCanceledVsExpiredTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private BillingAccount $billingAccount;
    private Plan $plan;
    private PlanPrice $monthlyPrice;
    private SubscriptionRepository $subscriptionRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        // Create starter plan (required by StartTrialAction)
        Plan::create([
            'code' => 'starter',
            'name' => json_encode(['en' => 'Starter Plan']),
            'description' => json_encode(['en' => 'Starter tier']),
            'tier' => 'starter',
            'is_public' => true,
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 1,
        ]);
        
        // Create professional plan for checkout tests
        $this->plan = Plan::create([
            'code' => 'professional',
            'name' => json_encode(['en' => 'Professional Plan']),
            'description' => json_encode(['en' => 'Professional tier']),
            'tier' => 'growth',
            'is_public' => true,
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 2,
        ]);
        
        $this->monthlyPrice = PlanPrice::create([
            'plan_id' => $this->plan->id,
            'billing_cycle' => BillingCycleEnum::MONTHLY->value,
            'currency' => 'USD',
            'amount_cents' => 4900,
            'provider' => 'stripe',
            'provider_price_id' => 'price_test_monthly',
            'is_active' => true,
        ]);

        $this->subscriptionRepo = app(SubscriptionRepository::class);
    }

    /**
     * Helper: Create store without triggering SQLite GREATEST() function error.
     */
    private function createStore(): int
    {
        return \DB::table('stores')->insertGetId([
            'name' => 'Test Store',
            'slug' => 'test-store-' . uniqid(),
            'owner_id' => $this->user->id,
            'is_active' => true,
            'currency' => 'USD',
            'timezone' => 'UTC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * REPRO: Two abandoned checkouts → second one should be EXPIRED, not CANCELED.
     * CRITICAL: getActiveForAccount() must return the trial, not the abandoned checkout.
     */
    public function test_abandoned_checkout_uses_expired_status_not_canceled(): void
    {
        $storeId = $this->createStore();

        // 1. Start trial
        $trialAction = app(StartTrialAction::class);
        $trialSubscription = $trialAction->execute(new StartTrialDTO(
            ownerUserId: $this->user->id,
            storeId: $storeId,
            planCode: 'starter',
        ));

        $this->assertEquals(SubscriptionStatusEnum::TRIALING, $trialSubscription->status);
        $sub1Id = $trialSubscription->id;

        // 2. First checkout (abandoned)
        $checkoutAction = app(CreateCheckoutSessionAction::class);
        $checkoutAction->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $trialSubscription->billing_account_id,
            planPriceId: $this->monthlyPrice->id,
            storeId: $storeId,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        // Verify: trial still exists
        $trialSubscription->refresh();
        $this->assertEquals(SubscriptionStatusEnum::TRIALING, $trialSubscription->status);

        // Verify: incomplete subscription created
        $incomplete1 = Subscription::where('billing_account_id', $trialSubscription->billing_account_id)
            ->where('status', SubscriptionStatusEnum::INCOMPLETE)
            ->first();
        $this->assertNotNull($incomplete1);
        $this->assertEquals($sub1Id, $incomplete1->replaces_subscription_id);

        // 3. Second checkout (abandons first) — THIS IS THE CRITICAL TEST
        $checkoutAction->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $trialSubscription->billing_account_id,
            planPriceId: $this->monthlyPrice->id,
            storeId: $storeId,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        // ASSERT: First incomplete is now EXPIRED (not CANCELED)
        $incomplete1->refresh();
        $this->assertEquals(
            SubscriptionStatusEnum::EXPIRED,
            $incomplete1->status,
            'Abandoned incomplete checkout must use EXPIRED status (via state machine), not CANCELED'
        );
        $this->assertNotNull($incomplete1->ended_at, 'Expired subscription must have ended_at set');

        // ASSERT: Trial subscription unchanged
        $trialSubscription->refresh();
        $this->assertEquals(
            SubscriptionStatusEnum::TRIALING,
            $trialSubscription->status,
            'Trial subscription must remain TRIALING — abandoned checkout should not affect it'
        );
        $this->assertNull($trialSubscription->canceled_at);

        // CRITICAL ASSERT: getActiveForAccount returns TRIAL, not expired checkout
        $activeSubscription = $this->subscriptionRepo->getActiveForAccount($trialSubscription->billing_account_id);
        $this->assertNotNull($activeSubscription, 'Must return an active subscription');
        $this->assertEquals(
            $sub1Id,
            $activeSubscription->id,
            'getActiveForAccount() must return the trial subscription, not the expired abandoned checkout'
        );
        $this->assertEquals(
            SubscriptionStatusEnum::TRIALING,
            $activeSubscription->status
        );
    }

    /**
     * REGRESSION: API GET /billing/subscription must return trial, not abandoned checkout.
     */
    public function test_api_returns_trial_subscription_not_abandoned_checkout(): void
    {
        $storeId = $this->createStore();
        
        // Setup: trial + abandoned checkout
        $trialAction = app(StartTrialAction::class);
        $trialSubscription = $trialAction->execute(new StartTrialDTO(
            ownerUserId: $this->user->id,
            storeId: $storeId,
            planCode: 'starter',
        ));

        $checkoutAction = app(CreateCheckoutSessionAction::class);
        $checkoutAction->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $trialSubscription->billing_account_id,
            planPriceId: $this->monthlyPrice->id,
            storeId: $storeId,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        // Second checkout (abandons first)
        $checkoutAction->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $trialSubscription->billing_account_id,
            planPriceId: $this->monthlyPrice->id,
            storeId: $storeId,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        // ASSERT: Repository returns trial
        $activeSubscription = $this->subscriptionRepo->getActiveForAccount($trialSubscription->billing_account_id);
        $this->assertEquals($trialSubscription->id, $activeSubscription->id);
        $this->assertEquals(SubscriptionStatusEnum::TRIALING, $activeSubscription->status);

        // ASSERT: API endpoint would return correct subscription
        $this->actingAs($this->user);
        $response = $this->getJson('/api/v1/users/billing/subscription');
        
        $response->assertOk();
        $response->assertJsonPath('data.subscription.id', $trialSubscription->id);
        $response->assertJsonPath('data.subscription.status', 'trialing');
        $response->assertJsonPath('data.has_active_subscription', true);
    }

    /**
     * REGRESSION: Upgrade/Cancel actions must operate on trial, not abandoned checkout.
     */
    public function test_billing_actions_target_trial_not_abandoned_checkout(): void
    {
        $storeId = $this->createStore();
        
        // Setup: trial + abandoned checkout
        $trialAction = app(StartTrialAction::class);
        $trialSubscription = $trialAction->execute(new StartTrialDTO(
            ownerUserId: $this->user->id,
            storeId: $storeId,
            planCode: 'starter',
        ));

        $checkoutAction = app(CreateCheckoutSessionAction::class);
        $checkoutAction->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $trialSubscription->billing_account_id,
            planPriceId: $this->monthlyPrice->id,
            storeId: $storeId,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        // Second checkout (abandons first)
        $checkoutAction->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $trialSubscription->billing_account_id,
            planPriceId: $this->monthlyPrice->id,
            storeId: $storeId,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        // TEST: CancelSubscriptionAction targets trial
        $cancelAction = app(CancelSubscriptionAction::class);
        $canceledSubscription = $cancelAction->execute(new CancelSubscriptionDTO(
            billingAccountId: $trialSubscription->billing_account_id,
            cancelImmediately: true,
            reason: 'testing',
            actorUserId: $this->user->id,
        ));

        $this->assertEquals(
            $trialSubscription->id,
            $canceledSubscription->id,
            'CancelSubscriptionAction must cancel the trial, not the abandoned checkout'
        );
        $this->assertEquals(SubscriptionStatusEnum::CANCELED, $canceledSubscription->status);
    }

    /**
     * REGRESSION: Real paid subscription with cancel_at_period_end MUST remain in
     * scopeWithAccess() until period ends (existing behavior must not break).
     */
    public function test_real_canceled_subscription_still_grants_access_during_period(): void
    {
        // Create active subscription with provider_subscription_id (real paid subscription)
        $billingAccount = BillingAccount::factory()->create(['owner_user_id' => $this->user->id]);
        $subscription = Subscription::factory()->create([
            'billing_account_id' => $billingAccount->id,
            'status' => SubscriptionStatusEnum::ACTIVE,
            'provider_subscription_id' => 'sub_stripe_test_123',
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->monthlyPrice->id,
            'current_period_starts_at' => now()->subDays(10),
            'current_period_ends_at' => now()->addDays(20),
        ]);

        // Cancel via normal action (simulates user cancellation)
        $cancelAction = app(CancelSubscriptionAction::class);
        $canceledSubscription = $cancelAction->execute(new CancelSubscriptionDTO(
            billingAccountId: $billingAccount->id,
            cancelImmediately: false, // cancel_at_period_end
            reason: 'user_requested',
            actorUserId: $this->user->id,
        ));

        $this->assertEquals(SubscriptionStatusEnum::CANCELED, $canceledSubscription->status);
        $this->assertTrue($canceledSubscription->cancel_at_period_end);

        // ASSERT: Still appears in getActiveForAccount() (grants access until period end)
        $activeSubscription = $this->subscriptionRepo->getActiveForAccount($billingAccount->id);
        $this->assertNotNull(
            $activeSubscription,
            'Canceled subscription with cancel_at_period_end must still grant access until period ends'
        );
        $this->assertEquals($subscription->id, $activeSubscription->id);
    }

    /**
     * EDGE CASE: Multiple incomplete subscriptions abandoned in sequence.
     */
    public function test_multiple_abandoned_checkouts_all_become_expired(): void
    {
        $storeId = $this->createStore();
        
        $trialAction = app(StartTrialAction::class);
        $trialSubscription = $trialAction->execute(new StartTrialDTO(
            ownerUserId: $this->user->id,
            storeId: $storeId,
            planCode: 'starter',
        ));

        $checkoutAction = app(CreateCheckoutSessionAction::class);

        // Create 3 checkouts in sequence (each abandons previous)
        for ($i = 0; $i < 3; $i++) {
            $checkoutAction->execute(new CreateCheckoutSessionDTO(
                billingAccountId: $trialSubscription->billing_account_id,
                planPriceId: $this->monthlyPrice->id,
                storeId: $storeId,
                successUrl: 'https://example.com/success',
                cancelUrl: 'https://example.com/cancel',
            ));
        }

        // ASSERT: First 2 are EXPIRED, last is INCOMPLETE
        $allSubscriptions = Subscription::where('billing_account_id', $trialSubscription->billing_account_id)
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $allSubscriptions); // 1 trial + 3 checkouts
        $this->assertEquals(SubscriptionStatusEnum::TRIALING, $allSubscriptions[0]->status);
        $this->assertEquals(SubscriptionStatusEnum::EXPIRED, $allSubscriptions[1]->status);
        $this->assertEquals(SubscriptionStatusEnum::EXPIRED, $allSubscriptions[2]->status);
        $this->assertEquals(SubscriptionStatusEnum::INCOMPLETE, $allSubscriptions[3]->status);

        // CRITICAL: getActiveForAccount still returns trial
        $active = $this->subscriptionRepo->getActiveForAccount($trialSubscription->billing_account_id);
        $this->assertEquals($trialSubscription->id, $active->id);
    }

    /**
     * INTEGRATION: Verify state machine event is created for INCOMPLETE → EXPIRED transition.
     */
    public function test_state_machine_creates_event_for_expired_transition(): void
    {
        $trialAction = app(StartTrialAction::class);
        $trialSubscription = $trialAction->execute(new StartTrialDTO(
            ownerUserId: $this->user->id,
            storeId: null,
            planCode: 'starter',
        ));

        $store = Store::where('owner_id', $this->user->id)->first();

        $checkoutAction = app(CreateCheckoutSessionAction::class);
        
        // First checkout
        $checkoutAction->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $trialSubscription->billing_account_id,
            planPriceId: $this->monthlyPrice->id,
            storeId: $store->id,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        $incomplete1 = Subscription::where('billing_account_id', $trialSubscription->billing_account_id)
            ->where('status', SubscriptionStatusEnum::INCOMPLETE)
            ->first();

        // Second checkout (expires first)
        $checkoutAction->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $trialSubscription->billing_account_id,
            planPriceId: $this->monthlyPrice->id,
            storeId: $store->id,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        // ASSERT: SubscriptionEvent created with correct reason
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $incomplete1->id,
            'from_status' => 'incomplete',
            'to_status' => 'expired',
            'source' => 'system',
            'reason' => 'superseded_by_new_checkout',
        ]);
    }
}
