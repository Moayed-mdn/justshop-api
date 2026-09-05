<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Actions\Billing\CreatePlanAction;
use App\Actions\Subscription\ActivateSubscriptionAction;
use App\Actions\Subscription\CancelSubscriptionAction;
use App\Actions\Subscription\StartTrialAction;
use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Billing\CreatePlanDTO;
use App\DTOs\Subscription\ActivateSubscriptionDTO;
use App\DTOs\Subscription\CancelSubscriptionDTO;
use App\DTOs\Subscription\StartTrialDTO;
use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\Subscription\SubscriptionEventTypeEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Exceptions\Subscription\InvalidSubscriptionTransitionException;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\TestBillingProvider;
use App\Services\Billing\Webhooks\HandleCheckoutSessionCompleted;
use App\Services\Billing\Webhooks\HandleInvoicePaymentFailed;
use App\Services\Billing\Webhooks\HandleInvoicePaymentSucceeded;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full subscription lifecycle: trialing -> active -> canceled / past_due -> grace_period ->
 * reactivated, driven through the real Action/Handler classes the app dispatches from
 * merchant requests and Stripe webhooks (StartTrialAction, ActivateSubscriptionAction via
 * HandleCheckoutSessionCompleted, CancelSubscriptionAction, MarkPastDueAction and
 * EnterGracePeriodAction via HandleInvoicePaymentFailed, ReactivateSubscriptionAction via
 * HandleInvoicePaymentSucceeded). Assertions check real DB state: subscriptions.status and
 * the subscription_events audit trail — never a self-reported "readiness" value.
 *
 * BillingProviderInterface is rebound to TestBillingProvider (the app's own fake, normally
 * selected via BILLING_PROVIDER=test) in setUp because CancelSubscriptionAction unconditionally
 * calls BillingProviderInterface::cancelSubscription(), and this environment must not perform
 * real Stripe API calls. HandleInvoicePaymentFailed/Succeeded don't touch the billing provider
 * directly, so they run against whatever the app resolves in this environment either way.
 *
 * NOTE ON invoice.payment_succeeded payloads: this file deliberately keeps amount_paid at 0
 * cents so HandleInvoicePaymentSucceeded takes its "zero amount, no payment transaction
 * needed" branch. A non-zero amount_paid with a payment_intent/charge reference would make
 * the handler call the real Stripe SDK (\Stripe\PaymentIntent::retrieve), which this
 * environment must not do.
 */
class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Plan $plan;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable Store observer to avoid SQLite GREATEST function issue in tests
        // (same workaround used by StripeConnectSplitPaymentTest / PlatformOrderSecurityTest).

        // Avoid any real Stripe API calls from CancelSubscriptionAction.
        $this->app->bind(BillingProviderInterface::class, TestBillingProvider::class);

        $this->owner = User::factory()->create();

        $this->plan = $this->app->make(CreatePlanAction::class)->execute(new CreatePlanDTO(
            code: 'lifecycle-starter',
            name: ['en' => 'Lifecycle Starter'],
            description: null,
            tier: 'starter',
            tierRank: 1,
            isPublic: true,
            isActive: true,
            trialDays: 14,
            sortOrder: 1,
            metadata: null,
            features: [
                ['featureKey' => 'products.max', 'valueType' => 'limit', 'limitValue' => 100, 'booleanValue' => null],
            ],
            prices: [
                ['billingCycle' => 'monthly', 'currency' => 'USD', 'amountCents' => 2900],
            ],
        ));

        $this->store = Store::factory()->create(['owner_id' => $this->owner->id]);
    }

    private function startTrial(): Subscription
    {
        return $this->app->make(StartTrialAction::class)->execute(new StartTrialDTO(
            ownerUserId: $this->owner->id,
            storeId: $this->store->id,
            planCode: $this->plan->code,
        ));
    }

    /**
     * Activate a subscription the same way HandleCheckoutSessionCompleted does, with a
     * concrete current_period_ends_at (required by CancelSubscriptionAction's "cancel at
     * period end" branch, which calls ->toIso8601String() on it).
     */
    private function activate(Subscription $subscription, string $providerSubscriptionId): Subscription
    {
        return $this->app->make(ActivateSubscriptionAction::class)->execute(new ActivateSubscriptionDTO(
            subscriptionId: $subscription->id,
            providerSubscriptionId: $providerSubscriptionId,
            providerStatus: 'active',
            currentPeriodStartsAt: now(),
            currentPeriodEndsAt: now()->addMonth(),
        ));
    }

    private function invoiceEvent(
        string $eventId,
        string $type,
        string $providerSubscriptionId,
        array $overrides = []
    ): array {
        return [
            'id' => $eventId,
            'type' => $type,
            'data' => [
                'object' => array_merge([
                    'id' => 'in_' . $eventId,
                    'subscription' => $providerSubscriptionId,
                    'status' => 'open',
                    'currency' => 'usd',
                    'subtotal' => 2900,
                    'total' => 2900,
                    'amount_paid' => 0,
                    'amount_due' => 2900,
                    'attempt_count' => 1,
                    'next_payment_attempt' => now()->addDays(3)->timestamp,
                ], $overrides),
            ],
        ];
    }

    public function test_new_store_starts_subscription_in_trialing_status(): void
    {
        $subscription = $this->startTrial();

        $this->assertSame(SubscriptionStatusEnum::TRIALING->value, $subscription->status->value);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventTypeEnum::TRIAL_STARTED->value,
            'from_status' => null,
            'to_status' => SubscriptionStatusEnum::TRIALING->value,
        ]);
    }

    public function test_checkout_completion_converts_trial_to_active(): void
    {
        $subscription = $this->startTrial();

        $event = [
            'id' => 'evt_lifecycle_checkout',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_lifecycle_test',
                    'mode' => 'subscription',
                    'subscription' => 'sub_lifecycle_test',
                    'metadata' => [
                        'billing_account_id' => (string) $subscription->billing_account_id,
                        'local_subscription_id' => (string) $subscription->id,
                    ],
                ],
            ],
        ];

        $this->app->make(HandleCheckoutSessionCompleted::class)->handle($event);

        $subscription->refresh();
        $this->assertSame(SubscriptionStatusEnum::ACTIVE->value, $subscription->status->value);
        $this->assertSame('sub_lifecycle_test', $subscription->provider_subscription_id);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventTypeEnum::TRIAL_CONVERTED->value,
            'from_status' => SubscriptionStatusEnum::TRIALING->value,
            'to_status' => SubscriptionStatusEnum::ACTIVE->value,
        ]);
    }

    public function test_merchant_cancellation_at_period_end_keeps_subscription_active_until_period_end(): void
    {
        $subscription = $this->activate($this->startTrial(), 'sub_cancel_at_period_end');

        $this->app->make(CancelSubscriptionAction::class)->execute(new CancelSubscriptionDTO(
            billingAccountId: $subscription->billing_account_id,
            cancelImmediately: false,
            reason: 'too_expensive',
            actorUserId: $this->owner->id,
        ));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatusEnum::ACTIVE->value, $subscription->status->value);
        $this->assertTrue((bool) $subscription->cancel_at_period_end);
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_merchant_immediate_cancellation_transitions_subscription_to_canceled(): void
    {
        $subscription = $this->activate($this->startTrial(), 'sub_cancel_immediately');

        $this->app->make(CancelSubscriptionAction::class)->execute(new CancelSubscriptionDTO(
            billingAccountId: $subscription->billing_account_id,
            cancelImmediately: true,
            reason: 'switching_providers',
            actorUserId: $this->owner->id,
        ));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatusEnum::CANCELED->value, $subscription->status->value);
        $this->assertFalse((bool) $subscription->cancel_at_period_end);
        $this->assertNotNull($subscription->canceled_at);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventTypeEnum::CANCELED->value,
            'from_status' => SubscriptionStatusEnum::ACTIVE->value,
            'to_status' => SubscriptionStatusEnum::CANCELED->value,
            'source' => 'merchant',
        ]);
    }

    public function test_invoice_payment_failure_marks_active_subscription_past_due(): void
    {
        $subscription = $this->activate($this->startTrial(), 'sub_past_due_test');

        $this->app->make(HandleInvoicePaymentFailed::class)->handle(
            $this->invoiceEvent('evt_invoice_failed_1', 'invoice.payment_failed', 'sub_past_due_test', [
                'attempt_count' => 1,
            ])
        );

        $subscription->refresh();
        $this->assertSame(SubscriptionStatusEnum::PAST_DUE->value, $subscription->status->value);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventTypeEnum::PAYMENT_FAILED->value,
            'from_status' => SubscriptionStatusEnum::ACTIVE->value,
            'to_status' => SubscriptionStatusEnum::PAST_DUE->value,
        ]);
        $this->assertDatabaseHas('invoices', [
            'billing_account_id' => $subscription->billing_account_id,
            'subscription_id' => $subscription->id,
            'status' => 'open',
        ]);
    }

    public function test_repeated_payment_failures_exhausting_retries_moves_subscription_to_grace_period(): void
    {
        $subscription = $this->activate($this->startTrial(), 'sub_grace_period_test');

        // Stripe Smart Retries: 4th failed attempt with no further retry scheduled means
        // retries are exhausted -> grace_period, not past_due.
        $this->app->make(HandleInvoicePaymentFailed::class)->handle(
            $this->invoiceEvent('evt_invoice_failed_exhausted', 'invoice.payment_failed', 'sub_grace_period_test', [
                'attempt_count' => 4,
                'next_payment_attempt' => null,
            ])
        );

        $subscription->refresh();
        $this->assertSame(SubscriptionStatusEnum::GRACE_PERIOD->value, $subscription->status->value);
        $this->assertNotNull($subscription->grace_period_ends_at);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventTypeEnum::GRACE_PERIOD_STARTED->value,
            'from_status' => SubscriptionStatusEnum::ACTIVE->value,
            'to_status' => SubscriptionStatusEnum::GRACE_PERIOD->value,
        ]);
    }

    public function test_payment_recovery_reactivates_past_due_subscription(): void
    {
        $subscription = $this->activate($this->startTrial(), 'sub_recovery_test');

        $this->app->make(HandleInvoicePaymentFailed::class)->handle(
            $this->invoiceEvent('evt_invoice_failed_before_recovery', 'invoice.payment_failed', 'sub_recovery_test', [
                'attempt_count' => 1,
            ])
        );
        $subscription->refresh();
        $this->assertSame(SubscriptionStatusEnum::PAST_DUE->value, $subscription->status->value);

        // amount_paid stays 0 to avoid the real-Stripe-API branch (see class docblock).
        $this->app->make(HandleInvoicePaymentSucceeded::class)->handle(
            $this->invoiceEvent('evt_invoice_recovered', 'invoice.payment_succeeded', 'sub_recovery_test', [
                'status' => 'paid',
                'amount_paid' => 0,
                'amount_due' => 0,
            ])
        );

        $subscription->refresh();
        $this->assertSame(SubscriptionStatusEnum::ACTIVE->value, $subscription->status->value);
        $this->assertNull($subscription->grace_period_ends_at);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventTypeEnum::PAYMENT_RECOVERED->value,
            'from_status' => SubscriptionStatusEnum::PAST_DUE->value,
            'to_status' => SubscriptionStatusEnum::ACTIVE->value,
        ]);
    }

    /**
     * REGRESSION / DEFECT-DOCUMENTING TEST — currently fails against real app code.
     *
     * MarkPastDueAction, EnterGracePeriodAction, ReactivateSubscriptionAction, and
     * SuspendSubscriptionAction all read `$subscription->billingAccount->user->stores` to
     * find which stores' entitlements need recomputing after a status change. But
     * App\Models\BillingAccount only defines an owner() relation (belongsTo(User::class,
     * 'owner_user_id')) — there is no user() relation. Accessing ->user on the model
     * silently evaluates to null (Eloquent does not throw for an undefined relation
     * accessed as a property), and `foreach (null as $store)` is a silent PHP warning, not
     * an exception. So the DB transaction commits the status change but the entitlements
     * recompute step is a silent no-op — merchants incorrectly keep whatever
     * entitlement_status they had before entering past_due.
     *
     * The correct pattern already exists in the same codebase: ActivateSubscriptionAction
     * uses `Store::where('owner_id', $subscription->billingAccount->owner_user_id)->get()`.
     *
     * This test encodes the intended contract (per MarkPastDueAction's own docblock:
     * "Status: past_due -> entitlement_status: read_only") rather than the current buggy
     * behavior, so it will start passing once the four affected classes are fixed.
     */
    public function test_past_due_status_downgrades_store_entitlements_to_read_only(): void
    {
        $subscription = $this->activate($this->startTrial(), 'sub_entitlement_test');

        // Sanity check: activation already recomputed entitlements to ENTITLED via the
        // correct owner_id-based store lookup in ActivateSubscriptionAction.
        $this->assertDatabaseHas('store_entitlement_snapshots', [
            'store_id' => $this->store->id,
            'entitlement_status' => EntitlementStatusEnum::ENTITLED->value,
        ]);

        $this->app->make(HandleInvoicePaymentFailed::class)->handle(
            $this->invoiceEvent('evt_invoice_failed_entitlement', 'invoice.payment_failed', 'sub_entitlement_test', [
                'attempt_count' => 1,
            ])
        );

        $subscription->refresh();
        $this->assertSame(SubscriptionStatusEnum::PAST_DUE->value, $subscription->status->value);

        $this->assertDatabaseHas('store_entitlement_snapshots', [
            'store_id' => $this->store->id,
            'entitlement_status' => EntitlementStatusEnum::READ_ONLY->value,
        ]);
    }

    public function test_expired_subscription_cannot_transition_directly_to_past_due(): void
    {
        // Important edge case: EXPIRED is meant to be terminal except for explicit
        // resubscription (EXPIRED -> TRIALING / ACTIVE). A stray invoice.payment_failed
        // for an already-expired subscription must not silently resurrect it as past_due.
        $subscription = $this->startTrial();

        $this->app->make(SubscriptionStateMachine::class)->transition(
            $subscription,
            SubscriptionStatusEnum::EXPIRED,
            source: 'system',
            reason: 'trial_expired_without_conversion',
        );
        $subscription->refresh();
        $this->assertSame(SubscriptionStatusEnum::EXPIRED->value, $subscription->status->value);

        $this->expectException(InvalidSubscriptionTransitionException::class);

        $this->app->make(SubscriptionStateMachine::class)->transition(
            $subscription,
            SubscriptionStatusEnum::PAST_DUE,
            source: 'webhook',
            reason: 'stray_invoice_payment_failed',
        );
    }
}
