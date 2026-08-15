<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Billing\CreateCheckoutSessionDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\BillingCustomer;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Repositories\Billing\BillingAccountRepository;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateCheckoutSessionAction
{
    public function __construct(
        private BillingProviderInterface $billingProvider,
        private BillingAccountRepository $billingAccountRepository,
        private EnsureBillingCustomerAction $ensureBillingCustomerAction,
        private SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Create a Stripe Checkout Session for subscription signup or upgrade.
     * 
     * Cancels existing active subscriptions in Stripe first (to prevent double billing),
     * creates a local subscription record, then redirects to Stripe.
     * 
     * @return array ['session_id' => string, 'url' => string, 'expires_at' => int]
     */
    public function execute(CreateCheckoutSessionDTO $dto): array
    {
        // Get billing account
        $billingAccount = $this->billingAccountRepository->findByIdOrFail($dto->billingAccountId);

        // Ensure billing customer exists (with Stripe customer)
        $billingCustomer = $this->ensureBillingCustomerAction->execute($billingAccount);

        // Get plan price
        $planPrice = PlanPrice::with('plan')->findOrFail($dto->planPriceId);

        // Cancel existing abandoned "incomplete" subscriptions immediately
        // These were never paid and never granted access
        $existingSubscriptions = Subscription::where('billing_account_id', $billingAccount->id)
            ->whereIn('status', ['active', 'trialing', 'past_due', 'incomplete'])
            ->get();

        $liveSubscription = null;
        $abandonedIncomplete = [];

        foreach ($existingSubscriptions as $existingSub) {
            if ($existingSub->status->value === 'incomplete') {
                // Safe to cancel immediately - never granted access
                $abandonedIncomplete[] = $existingSub;
            } else {
                // Live subscription - defer cancellation until new one is confirmed
                $liveSubscription = $existingSub;
            }
        }

        // Cancel abandoned incomplete subscriptions immediately
        foreach ($abandonedIncomplete as $incompleteSub) {
            // Cancel in Stripe first (if linked)
            if ($incompleteSub->provider_subscription_id) {
                try {
                    $this->billingProvider->cancelSubscription(
                        $incompleteSub,
                        immediately: true
                    );
                    
                    Log::channel('billing')->info('checkout.existing_subscription_canceled_in_stripe', [
                        'subscription_id' => $incompleteSub->id,
                        'provider_subscription_id' => $incompleteSub->provider_subscription_id,
                    ]);
                } catch (\Exception $e) {
                    Log::channel('billing')->error('checkout.stripe_cancellation_failed', [
                        'subscription_id' => $incompleteSub->id,
                        'provider_subscription_id' => $incompleteSub->provider_subscription_id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue anyway - local cancellation will proceed
                }
            }

            // Transition to EXPIRED (not CANCELED) via state machine
            // INCOMPLETE → EXPIRED is allowed and semantically correct for abandoned checkouts
            // that never granted access (vs CANCELED which implies an active subscription was terminated)
            $this->stateMachine->transition(
                subscription: $incompleteSub,
                toStatus: SubscriptionStatusEnum::EXPIRED,
                source: 'system',
                reason: 'superseded_by_new_checkout',
            );

            // Mark as ended
            $incompleteSub->update([
                'ended_at' => Carbon::now(),
            ]);
        }

        // If there's a live subscription, mark it for deferred cancellation
        if ($liveSubscription) {
            Log::channel('billing')->info('checkout_session.deferred_cancellation', [
                'old_subscription_id' => $liveSubscription->id,
                'old_subscription_status' => $liveSubscription->status->value,
            ]);
        }

        // Create new local subscription BEFORE redirecting to Stripe
        // Calculate trial eligibility (prevent trial gaming)
        // Trial is only granted if:
        // 1. The billing account has never used a trial (trial_used = false)
        // 2. There's no existing live subscription (this is the first subscription)
        $isEligibleForTrial = !$billingAccount->trial_used && $liveSubscription === null;
        $trialDays = $isEligibleForTrial ? ($planPrice->plan->trial_days ?? 0) : 0;
        $trialEndsAt = $trialDays > 0 ? Carbon::now()->addDays($trialDays) : null;

        $subscription = Subscription::create([
            'billing_account_id' => $billingAccount->id,
            'replaces_subscription_id' => $liveSubscription?->id,
            'plan_id' => $planPrice->plan_id,
            'plan_price_id' => $planPrice->id,
            'billing_cycle' => $planPrice->billing_cycle,
            'status' => 'incomplete',
            'provider' => 'stripe',
            'trial_ends_at' => $trialEndsAt,
        ]);

        // Create checkout session with local_subscription_id in metadata
        // This allows webhooks to find the exact subscription without ambiguity
        // Pass trial_days to billing provider so Stripe honors the trial period
        $session = $this->billingProvider->createCheckoutSession(
            $billingCustomer,
            $planPrice->provider_price_id,
            $dto->successUrl,
            $dto->cancelUrl,
            [
                'plan_id' => $planPrice->plan_id,
                'plan_price_id' => $planPrice->id,
                'store_id' => $dto->storeId ?? null,
                'billing_account_id' => $billingAccount->id,
                'local_subscription_id' => $subscription->id, // ← Critical: enables precise webhook matching
                'replaces_subscription_id' => $liveSubscription?->id, // ← Enables webhook-driven cancellation
            ],
            $trialDays > 0 ? $trialDays : null // Only pass trial if > 0
        );

        Log::channel('billing')->info('checkout_session.created', [
            'billing_account_id' => $billingAccount->id,
            'plan_price_id' => $dto->planPriceId,
            'subscription_id' => $subscription->id,
            'session_id' => $session['session_id'],
            'trial_days' => $trialDays,
            'trial_eligible' => $isEligibleForTrial,
            'trial_already_used' => $billingAccount->trial_used,
            'has_live_subscription' => $liveSubscription !== null,
            'canceled_incomplete' => count($abandonedIncomplete),
            'deferred_live_subscription_id' => $liveSubscription?->id,
        ]);

        return $session;
    }
}
