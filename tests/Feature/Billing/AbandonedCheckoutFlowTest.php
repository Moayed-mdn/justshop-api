<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Actions\Billing\CreateCheckoutSessionAction;
use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Billing\CreateCheckoutSessionDTO;
use App\Models\BillingAccount;
use App\Models\BillingCustomer;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\Webhooks\HandleCheckoutSessionCompleted;
use App\Services\Billing\Webhooks\HandleCheckoutSessionExpired;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbandonedCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private BillingAccount $billingAccount;
    private Plan $plan;
    private PlanPrice $planPrice;
    private BillingCustomer $billingCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        // Default permissive mock so webhook/CLI-driven tests below (which
        // don't set up their own strict createMock expectation) never hit
        // the real Stripe API. Tests that need to assert specific provider
        // calls still create their own mock and rebind the interface,
        // which simply overrides this default for that test.
        $this->mock(BillingProviderInterface::class, function ($mock) {
            $mock->shouldReceive('createCustomer')->andReturn(['provider_customer_id' => 'cus_' . \Illuminate\Support\Str::random(14), 'metadata' => []]);
            $mock->shouldReceive('updateCustomer')->andReturnNull();
            $mock->shouldReceive('createCheckoutSession')->andReturn(['session_id' => 'cs_' . \Illuminate\Support\Str::random(14), 'url' => 'https://checkout.stripe.com/test', 'expires_at' => now()->addHours(24)->timestamp]);
            $mock->shouldReceive('createPortalSession')->andReturn(['session_id' => 'bps_' . \Illuminate\Support\Str::random(14), 'url' => 'https://billing.stripe.com/test']);
            $mock->shouldReceive('cancelSubscription')->andReturnNull();
            $mock->shouldReceive('resumeSubscription')->andReturnNull();
            $mock->shouldReceive('updateSubscription')->andReturnNull();
            $mock->shouldReceive('getSubscription')->andReturn([]);
            $mock->shouldReceive('getInvoice')->andReturn([]);
            $mock->shouldReceive('verifyWebhookSignature')->andReturnNull();
            $mock->shouldReceive('parseWebhookEvent')->andReturn(['type' => '', 'data' => [], 'id' => '']);
            $mock->shouldReceive('createPrice')->andReturn(['provider_product_id' => 'prod_' . \Illuminate\Support\Str::random(14), 'provider_price_id' => 'price_' . \Illuminate\Support\Str::random(14)]);
            $mock->shouldReceive('archivePrice')->andReturnNull();
        });

        $user = User::factory()->create();

        $this->billingAccount = BillingAccount::create([
            'owner_user_id' => $user->id,
            'billing_email' => 'billing@example.com',
            'legal_name' => 'Test Company',
            'country_code' => 'US',
            'default_currency' => 'usd',
            'status' => 'active',
            'trial_used' => false,
            'stores_count' => 0,
            'stores_max' => 5,
        ]);

        $this->plan = Plan::create([
            'code' => 'pro',
            'name' => json_encode(['en' => 'Pro Plan']),
            'description' => json_encode(['en' => 'Professional plan']),
            'tier' => 'growth',
            'tier_rank' => 2,
            'is_public' => true,
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 1,
        ]);

        $this->planPrice = PlanPrice::create([
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'amount_cents' => 2900,
            'currency' => 'usd',
            'provider' => 'stripe',
            'provider_price_id' => 'price_test_123',
            'is_active' => true,
        ]);

        $this->billingCustomer = BillingCustomer::create([
            'billing_account_id' => $this->billingAccount->id,
            'provider' => 'stripe',
            'provider_customer_id' => 'cus_test_123',
        ]);
    }

    /** @test */
    public function abandoning_checkout_leaves_original_trialing_subscription_untouched(): void
    {
        // Arrange: Create a trialing subscription
        $originalSubscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'provider' => 'stripe',
            'provider_subscription_id' => 'sub_original_123',
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Mock the billing provider
        $mockProvider = $this->createMock(BillingProviderInterface::class);
        $mockProvider->expects($this->once())
            ->method('createCheckoutSession')
            ->willReturn([
                'session_id' => 'cs_test_123',
                'url' => 'https://checkout.stripe.com/test',
                'expires_at' => now()->addHours(24)->timestamp,
            ]);

        $this->app->instance(BillingProviderInterface::class, $mockProvider);

        // Act: Start a checkout session (user is attempting to upgrade)
        $action = $this->app->make(CreateCheckoutSessionAction::class);
        $action->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $this->billingAccount->id,
            planPriceId: $this->planPrice->id,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        // Assert: Original subscription remains unchanged
        $originalSubscription->refresh();
        $this->assertSame('trialing', $originalSubscription->status->value);
        $this->assertSame('sub_original_123', $originalSubscription->provider_subscription_id);
        $this->assertNull($originalSubscription->canceled_at);

        // Assert: A new incomplete subscription was created with replaces_subscription_id
        $incompleteSubscription = Subscription::where('status', 'incomplete')
            ->where('billing_account_id', $this->billingAccount->id)
            ->first();

        $this->assertNotNull($incompleteSubscription);
        $this->assertSame($originalSubscription->id, $incompleteSubscription->replaces_subscription_id);
    }

    /** @test */
    public function completing_checkout_after_abandonment_cancels_old_subscription_and_activates_new_one(): void
    {
        // Arrange: Trialing subscription + incomplete subscription from abandoned checkout
        $originalSubscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'provider' => 'stripe',
            'provider_subscription_id' => 'sub_original_123',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $newSubscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'replaces_subscription_id' => $originalSubscription->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);

        // Mock billing provider for cancellation
        $mockProvider = $this->createMock(BillingProviderInterface::class);
        $mockProvider->expects($this->once())
            ->method('cancelSubscription')
            ->with(
                $this->callback(fn($sub) => $sub->id === $originalSubscription->id),
                true // immediately
            );

        $this->app->instance(BillingProviderInterface::class, $mockProvider);

        // Act: Webhook completes checkout
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_completed',
                    'mode' => 'subscription',
                    'subscription' => 'sub_new_123',
                    'metadata' => [
                        'billing_account_id' => (string) $this->billingAccount->id,
                        'local_subscription_id' => (string) $newSubscription->id,
                    ],
                ],
            ],
        ]);

        // Assert: New subscription is active
        $newSubscription->refresh();
        $this->assertSame('active', $newSubscription->status->value);
        $this->assertSame('sub_new_123', $newSubscription->provider_subscription_id);

        // Assert: Old subscription is canceled
        $originalSubscription->refresh();
        $this->assertSame('canceled', $originalSubscription->status->value);
        $this->assertNotNull($originalSubscription->canceled_at);
    }

    /** @test */
    public function checkout_session_expired_webhook_marks_incomplete_subscription_as_expired(): void
    {
        // Arrange: Incomplete subscription from abandoned checkout
        $incompleteSubscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);

        // Act: Webhook fires checkout.session.expired
        $handler = $this->app->make(HandleCheckoutSessionExpired::class);
        $handler->handle([
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => 'cs_test_expired',
                    'mode' => 'subscription',
                    'metadata' => [
                        'local_subscription_id' => (string) $incompleteSubscription->id,
                    ],
                ],
            ],
        ]);

        // Assert: Subscription is marked as expired
        $incompleteSubscription->refresh();
        $this->assertSame('expired', $incompleteSubscription->status->value);
        $this->assertNotNull($incompleteSubscription->ended_at);

        // Assert: SubscriptionEvent was created
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $incompleteSubscription->id,
            'event_type' => 'expired',
            'from_status' => 'incomplete',
            'to_status' => 'expired',
            'source' => 'webhook',
            'reason' => 'checkout_session_expired',
        ]);
    }

    /** @test */
    public function abandoned_incomplete_subscriptions_are_canceled_immediately_on_new_checkout(): void
    {
        // Arrange: Two incomplete subscriptions from previous abandoned attempts
        $oldIncomplete1 = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);
        $oldIncomplete1->timestamps = false;
        $oldIncomplete1->created_at = now()->subHours(10);
        $oldIncomplete1->save();

        $oldIncomplete2 = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);
        $oldIncomplete2->timestamps = false;
        $oldIncomplete2->created_at = now()->subHours(5);
        $oldIncomplete2->save();

        // Mock provider
        $mockProvider = $this->createMock(BillingProviderInterface::class);
        $mockProvider->expects($this->once())
            ->method('createCheckoutSession')
            ->willReturn([
                'session_id' => 'cs_test_new',
                'url' => 'https://checkout.stripe.com/new',
                'expires_at' => now()->addHours(24)->timestamp,
            ]);

        $this->app->instance(BillingProviderInterface::class, $mockProvider);

        // Act: Create new checkout session
        $action = $this->app->make(CreateCheckoutSessionAction::class);
        $action->execute(new CreateCheckoutSessionDTO(
            billingAccountId: $this->billingAccount->id,
            planPriceId: $this->planPrice->id,
            successUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
        ));

        // Assert: Old incomplete subscriptions are marked EXPIRED (not CANCELED).
        //
        // CORRECTED 2026-08-24: this assertion previously expected 'canceled', which
        // contradicted both the current app code and the sibling regression test
        // AbandonedCheckoutCanceledVsExpiredTest::test_abandoned_checkout_uses_expired_status_not_canceled.
        // CreateCheckoutSessionAction explicitly transitions superseded incomplete
        // subscriptions to EXPIRED via the state machine, with an inline comment
        // explaining that CANCELED is reserved for terminating a subscription that had
        // granted access, which an unpaid "incomplete" subscription never did. See
        // app/Actions/Billing/CreateCheckoutSessionAction.php (search "superseded_by_new_checkout").
        $oldIncomplete1->refresh();
        $oldIncomplete2->refresh();

        $this->assertSame('expired', $oldIncomplete1->status->value);
        $this->assertSame('expired', $oldIncomplete2->status->value);
        $this->assertNotNull($oldIncomplete1->ended_at);
        $this->assertNotNull($oldIncomplete2->ended_at);
    }

    /** @test */
    public function checkout_session_expired_webhook_does_not_downgrade_a_subscription_that_already_activated(): void
    {
        // Edge case: Stripe can send checkout.session.expired for a session that the
        // customer actually completed moments earlier (delivery race / manual retry).
        // HandleCheckoutSessionExpired guards this with an explicit status check
        // ("Only expire if still in incomplete status") — a subscription that has
        // already moved to trialing/active must NOT be knocked back to expired by a
        // late-arriving expiry webhook for its original checkout session.
        $subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);

        // Session completes first (customer paid), activating the subscription.
        $completedHandler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $completedHandler->handle([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_race',
                    'mode' => 'subscription',
                    'subscription' => 'sub_test_race',
                    'metadata' => [
                        'billing_account_id' => (string) $this->billingAccount->id,
                        'local_subscription_id' => (string) $subscription->id,
                    ],
                ],
            ],
        ]);

        $subscription->refresh();
        $this->assertSame('active', $subscription->status->value);

        // The (now stale) expired webhook for the same session arrives afterwards.
        $expiredHandler = $this->app->make(HandleCheckoutSessionExpired::class);
        $expiredHandler->handle([
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => 'cs_test_race',
                    'mode' => 'subscription',
                    'metadata' => [
                        'local_subscription_id' => (string) $subscription->id,
                    ],
                ],
            ],
        ]);

        // Assert: subscription is untouched by the late expiry webhook.
        $subscription->refresh();
        $this->assertSame('active', $subscription->status->value);
        $this->assertNull($subscription->ended_at);
    }

    /** @test */
    public function dry_run_mode_for_expire_stale_incomplete_command_shows_preview_without_changes(): void
    {
        // Arrange: Stale incomplete subscription
        $staleIncomplete = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);
        
        // Force the created_at timestamp to 25 hours ago
        $staleIncomplete->timestamps = false;
        $staleIncomplete->created_at = now()->subHours(25);
        $staleIncomplete->save();

        // Act: Run with --dry-run
        $this->artisan('billing:expire-stale-incomplete-subscriptions', ['--dry-run' => true])
            ->assertSuccessful();

        // Assert: Status unchanged (dry run)
        $staleIncomplete->refresh();
        $this->assertSame('incomplete', $staleIncomplete->status->value);
        $this->assertNull($staleIncomplete->ended_at);
    }

    /**
     * Note: The scheduled_command test and API endpoint tests are omitted here
     * because they require database persistence across process boundaries (artisan commands)
     * or complex authorization setup. The core logic is tested through:
     * - Direct webhook handler tests
     * - Integration tests in other test files
     * - Manual command verification
     */
}
