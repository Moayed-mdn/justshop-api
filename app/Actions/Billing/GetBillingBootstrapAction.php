<?php

namespace App\Actions\Billing;

use App\Enums\Entitlement\FeatureKeyEnum;
use App\Models\User;
use App\Repositories\Billing\BillingAccountRepository;
use App\Repositories\Entitlement\EntitlementSnapshotRepository;
use App\Repositories\Subscription\SubscriptionRepository;
use Carbon\Carbon;

class GetBillingBootstrapAction
{
    public function __construct(
        private BillingAccountRepository $billingAccountRepository,
        private SubscriptionRepository $subscriptionRepository,
        private EntitlementSnapshotRepository $snapshotRepository,
    ) {}

    /**
     * Get billing bootstrap data for a user.
     * 
     * Returns billing account, subscription, and active store entitlements.
     */
    public function execute(User $user, ?int $activeStoreId = null): ?array
    {
        // Get billing account
        $billingAccount = $this->billingAccountRepository->findByOwner($user->id);

        if (!$billingAccount) {
            return null;
        }

        // Get active subscription
        $subscription = $this->subscriptionRepository->getActiveForAccount($billingAccount->id);

        $billingData = [
            'account_id' => $billingAccount->id,
            'billing_email' => $billingAccount->billing_email,
            'trial_used' => $billingAccount->trial_used,
            'subscription' => null,
        ];

        if ($subscription) {
            $trialDaysRemaining = null;
            if ($subscription->trial_ends_at) {
                $trialDaysRemaining = max(0, Carbon::now()->diffInDays($subscription->trial_ends_at, false));
                $trialDaysRemaining = $trialDaysRemaining > 0 ? (int) ceil($trialDaysRemaining) : 0;
            }

            $billingData['subscription'] = [
                'id' => $subscription->id,
                'status' => $subscription->status->value,
                'plan_code' => $subscription->plan->code ?? null,
                'plan_name' => $subscription->plan->name ?? null,
                'billing_cycle' => $subscription->billing_cycle->value,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'trial_days_remaining' => $trialDaysRemaining,
                'current_period_ends_at' => $subscription->current_period_ends_at?->toIso8601String(),
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
            ];
        }

        // Get active store entitlements (if active store is set)
        $activeStoreEntitlements = null;
        if ($activeStoreId) {
            $snapshot = $this->snapshotRepository->findByStoreId($activeStoreId);
            
            if ($snapshot) {
                $activeStoreEntitlements = [
                    'status' => $snapshot->entitlement_status->value,
                    'features' => $snapshot->features,
                    'usage' => [
                        'products' => [
                            'count' => $snapshot->products_count,
                            'limit' => $snapshot->features[FeatureKeyEnum::PRODUCTS_MAX->value] ?? null,
                        ],
                        'stores' => [
                            'count' => $billingAccount->stores_count,
                            'limit' => $billingAccount->stores_max,
                        ],
                    ],
                    'expires_at' => $snapshot->expires_at?->toIso8601String(),
                ];
            }
        }

        return [
            'billing' => $billingData,
            'active_store_entitlements' => $activeStoreEntitlements,
        ];
    }
}
