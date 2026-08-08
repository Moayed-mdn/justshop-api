<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Billing\CreateCheckoutSessionDTO;
use App\Models\BillingCustomer;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Repositories\Billing\BillingAccountRepository;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateCheckoutSessionAction
{
    public function __construct(
        private BillingProviderInterface $billingProvider,
        private BillingAccountRepository $billingAccountRepository,
        private EnsureBillingCustomerAction $ensureBillingCustomerAction,
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

        // Cancel existing active subscriptions (both in Stripe AND locally)
        // This prevents double billing and duplicate provider_subscription_id issues
        $existingSubscriptions = Subscription::where('billing_account_id', $billingAccount->id)
            ->whereIn('status', ['active', 'trialing', 'past_due', 'incomplete'])
            ->get();

        foreach ($existingSubscriptions as $existingSub) {
            // Cancel in Stripe first (if linked)
            if ($existingSub->provider_subscription_id) {
                try {
                    $this->billingProvider->cancelSubscription(
                        $existingSub,
                        immediately: true
                    );
                    
                    Log::channel('billing')->info('checkout.existing_subscription_canceled_in_stripe', [
                        'subscription_id' => $existingSub->id,
                        'provider_subscription_id' => $existingSub->provider_subscription_id,
                    ]);
                } catch (\Exception $e) {
                    Log::channel('billing')->error('checkout.stripe_cancellation_failed', [
                        'subscription_id' => $existingSub->id,
                        'provider_subscription_id' => $existingSub->provider_subscription_id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue anyway - local cancellation will proceed
                }
            }

            // Cancel locally
            $existingSub->update([
                'status' => 'canceled',
                'canceled_at' => Carbon::now(),
            ]);
        }

        // Create new local subscription BEFORE redirecting to Stripe
        $subscription = Subscription::create([
            'billing_account_id' => $billingAccount->id,
            'plan_id' => $planPrice->plan_id,
            'plan_price_id' => $planPrice->id,
            'billing_cycle' => $planPrice->billing_cycle,
            'status' => 'incomplete',
            'provider' => 'stripe',
            'trial_ends_at' => Carbon::now()->addDays(14),
        ]);

        // Create checkout session with local_subscription_id in metadata
        // This allows webhooks to find the exact subscription without ambiguity
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
            ]
        );

        Log::channel('billing')->info('checkout_session.created', [
            'billing_account_id' => $billingAccount->id,
            'plan_price_id' => $dto->planPriceId,
            'subscription_id' => $subscription->id,
            'session_id' => $session['session_id'],
            'canceled_existing' => $existingSubscriptions->count(),
        ]);

        return $session;
    }
}
