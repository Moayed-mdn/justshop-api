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
     * Create a Stripe Checkout Session for subscription signup.
     * 
     * Creates a local subscription record first, then redirects to Stripe.
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

        // Create or update local subscription BEFORE redirecting to Stripe
        // This allows webhooks to find and update it
        $subscription = Subscription::updateOrCreate(
            [
                'billing_account_id' => $billingAccount->id,
                'status' => 'incomplete',
            ],
            [
                'plan_id' => $planPrice->plan_id,
                'plan_price_id' => $planPrice->id,
                'billing_cycle' => $planPrice->billing_cycle,
                'status' => 'incomplete',
                'provider' => 'stripe',
                'trial_ends_at' => Carbon::now()->addDays(14),
            ]
        );

        // Create checkout session
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
            ]
        );

        Log::channel('billing')->info('checkout_session.created', [
            'billing_account_id' => $billingAccount->id,
            'plan_price_id' => $dto->planPriceId,
            'subscription_id' => $subscription->id,
            'session_id' => $session['session_id'],
        ]);

        return $session;
    }
}
