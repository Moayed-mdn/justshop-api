<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\Models\BillingAccount;
use App\Models\BillingCustomer;

class EnsureBillingCustomerAction
{
    public function __construct(
        private BillingProviderInterface $billingProvider
    ) {}

    /**
     * Get or create billing customer record for a billing account.
     * 
     * Phase 3: Creates actual Stripe customer if not exists.
     */
    public function execute(BillingAccount $account, string $provider = 'stripe'): BillingCustomer
    {
        $customer = BillingCustomer::where('billing_account_id', $account->id)
            ->where('provider', $provider)
            ->first();

        if (!$customer) {
            // Create Stripe customer first, then store the record
            $providerData = $this->billingProvider->createCustomer($account);

            $customer = BillingCustomer::create([
                'billing_account_id' => $account->id,
                'provider' => $provider,
                'provider_customer_id' => $providerData['provider_customer_id'],
                'default_payment_method_id' => null,
                'metadata' => $providerData['metadata'] ?? null,
            ]);
        } elseif (!$customer->provider_customer_id) {
            $providerData = $this->billingProvider->createCustomer($account);
            
            $customer->update([
                'provider_customer_id' => $providerData['provider_customer_id'],
                'metadata' => $providerData['metadata'] ?? null,
            ]);

            $customer->refresh();
        }

        return $customer;
    }
}
