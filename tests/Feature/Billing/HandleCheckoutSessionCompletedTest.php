<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Actions\Subscription\ActivateSubscriptionAction;
use App\Models\BillingAccount;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\Webhooks\HandleCheckoutSessionCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HandleCheckoutSessionCompletedTest extends TestCase
{
    use RefreshDatabase;

    private BillingAccount $billingAccount;
    private Plan $plan;
    private PlanPrice $planPrice;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    private function createCheckoutSessionEvent(
        int $billingAccountId,
        ?int $localSubscriptionId = null,
        string $stripeSubscriptionId = 'sub_test_123'
    ): array {
        $metadata = [
            'billing_account_id' => (string) $billingAccountId,
        ];

        if ($localSubscriptionId !== null) {
            $metadata['local_subscription_id'] = (string) $localSubscriptionId;
        }

        return [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_' . bin2hex(random_bytes(8)),
                    'mode' => 'subscription',
                    'subscription' => $stripeSubscriptionId,
                    'metadata' => $metadata,
                ],
            ],
        ];
    }

    public function test_activates_subscription_with_local_subscription_id_metadata(): void
    {
        // Arrange: Create subscription in incomplete status
        $subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);

        $event = $this->createCheckoutSessionEvent(
            billingAccountId: $this->billingAccount->id,
            localSubscriptionId: $subscription->id,
            stripeSubscriptionId: 'sub_stripe_123'
        );

        // Act: Handle webhook
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);

        // Assert: Subscription was activated with correct provider ID
        $subscription->refresh();
        $this->assertSame('active', $subscription->status->value);
        $this->assertSame('sub_stripe_123', $subscription->provider_subscription_id);
    }

    public function test_uses_local_subscription_id_strategy_when_metadata_present(): void
    {
        // Arrange: Create subscription
        $subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'provider' => 'stripe',
        ]);

        $event = $this->createCheckoutSessionEvent(
            billingAccountId: $this->billingAccount->id,
            localSubscriptionId: $subscription->id
        );

        // Act
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);

        // Assert: Subscription was activated (proving local_subscription_id path worked)
        $subscription->refresh();
        $this->assertSame('active', $subscription->status->value);
    }

    public function test_activates_correct_subscription_when_multiple_concurrent_trialing_subscriptions(): void
    {
        // Arrange: Create two concurrent trialing subscriptions
        $oldSubscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'provider' => 'stripe',
        ]);

        $newSubscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'provider' => 'stripe',
        ]);

        // Event targets the newer subscription specifically
        $event = $this->createCheckoutSessionEvent(
            billingAccountId: $this->billingAccount->id,
            localSubscriptionId: $newSubscription->id,
            stripeSubscriptionId: 'sub_new_123'
        );

        // Act
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);

        // Assert: Only the targeted subscription was activated
        $oldSubscription->refresh();
        $newSubscription->refresh();

        $this->assertSame('trialing', $oldSubscription->status->value);
        $this->assertNull($oldSubscription->provider_subscription_id);

        $this->assertSame('active', $newSubscription->status->value);
        $this->assertSame('sub_new_123', $newSubscription->provider_subscription_id);
    }

    public function test_logs_warning_and_falls_back_when_local_subscription_id_billing_account_mismatch(): void
    {
        // Arrange: Create subscription for different billing account
        $otherUser = User::factory()->create();
        $otherBillingAccount = BillingAccount::create([
            'owner_user_id' => $otherUser->id,
            'billing_email' => 'other@example.com',
            'legal_name' => 'Other Company',
            'country_code' => 'US',
            'default_currency' => 'usd',
            'status' => 'active',
            'trial_used' => false,
            'stores_count' => 0,
            'stores_max' => 5,
        ]);

        $wrongSubscription = Subscription::create([
            'billing_account_id' => $otherBillingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'provider' => 'stripe',
        ]);

        $correctSubscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);

        // Event has wrong local_subscription_id pointing to other billing account
        $event = $this->createCheckoutSessionEvent(
            billingAccountId: $this->billingAccount->id,
            localSubscriptionId: $wrongSubscription->id
        );

        // Act: Should fall back to billing_account_id matching
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);

        // Assert: Correct subscription was activated via fallback
        $wrongSubscription->refresh();
        $correctSubscription->refresh();

        $this->assertSame('trialing', $wrongSubscription->status->value);
        $this->assertSame('active', $correctSubscription->status->value);
    }

    public function test_fallback_to_billing_account_when_local_subscription_id_missing(): void
    {
        // Arrange: Create subscription without local_subscription_id in metadata
        $subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);

        // Event without local_subscription_id (backward compatibility)
        $event = $this->createCheckoutSessionEvent(
            billingAccountId: $this->billingAccount->id,
            localSubscriptionId: null
        );

        // Act
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);

        // Assert: Subscription was activated via fallback path
        $subscription->refresh();
        $this->assertSame('active', $subscription->status->value);
    }

    public function test_fallback_picks_most_recent_subscription_with_order_by_desc(): void
    {
        // Arrange: Create multiple subscriptions with different creation times
        $oldSubscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'provider' => 'stripe',
            'created_at' => now()->subHours(2),
        ]);

        sleep(1); // Ensure different IDs

        $newerSubscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
            'created_at' => now()->subHours(1),
        ]);

        // Event without local_subscription_id
        $event = $this->createCheckoutSessionEvent(
            billingAccountId: $this->billingAccount->id,
            localSubscriptionId: null
        );

        // Act
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);

        // Assert: Most recent (higher ID) subscription was activated
        $oldSubscription->refresh();
        $newerSubscription->refresh();

        $this->assertSame('trialing', $oldSubscription->status->value);
        $this->assertSame('active', $newerSubscription->status->value);
    }

    public function test_logs_warning_when_subscription_not_found(): void
    {
        // Arrange: Event with non-existent local_subscription_id
        $event = $this->createCheckoutSessionEvent(
            billingAccountId: $this->billingAccount->id,
            localSubscriptionId: 99999
        );

        // Expect warning log
        Log::shouldReceive('channel')
            ->with('billing')
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('warning')
            ->withArgs(function ($message, $context) {
                return $message === 'webhook.checkout.subscription_not_found'
                    && isset($context['local_subscription_id']);
            })
            ->once();

        // Act
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);

        // Assert: No subscriptions were activated
        $this->assertDatabaseMissing('subscriptions', [
            'billing_account_id' => $this->billingAccount->id,
            'status' => 'active',
        ]);
    }

    public function test_skips_non_subscription_mode_checkouts(): void
    {
        // Arrange
        $event = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_payment',
                    'mode' => 'payment', // Not subscription
                    'metadata' => [
                        'billing_account_id' => (string) $this->billingAccount->id,
                    ],
                ],
            ],
        ];

        // Expect info log about non-subscription mode
        Log::shouldReceive('channel')
            ->with('billing')
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return $message === 'webhook.checkout.non_subscription'
                    && $context['mode'] === 'payment';
            })
            ->once();

        // Act
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);
    }

    public function test_logs_warning_when_required_data_missing(): void
    {
        // Arrange: Event missing subscription ID
        $event = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_incomplete',
                    'mode' => 'subscription',
                    'metadata' => [
                        'billing_account_id' => (string) $this->billingAccount->id,
                    ],
                    // Missing 'subscription' field
                ],
            ],
        ];

        // Expect warning log
        Log::shouldReceive('channel')
            ->with('billing')
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('warning')
            ->withArgs(function ($message, $context) {
                return $message === 'webhook.checkout.missing_data';
            })
            ->once();

        // Act
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);
    }

    public function test_logs_activation_success(): void
    {
        // Arrange
        $subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'incomplete',
            'provider' => 'stripe',
        ]);

        $event = $this->createCheckoutSessionEvent(
            billingAccountId: $this->billingAccount->id,
            localSubscriptionId: $subscription->id,
            stripeSubscriptionId: 'sub_final_123'
        );

        // Act
        $handler = $this->app->make(HandleCheckoutSessionCompleted::class);
        $handler->handle($event);

        // Assert: Verify activation was successful
        $subscription->refresh();
        $this->assertSame('active', $subscription->status->value);
        $this->assertSame('sub_final_123', $subscription->provider_subscription_id);
    }
}
