<?php

namespace App\Actions\Entitlement;

use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\Entitlement\FeatureKeyEnum;
use App\Models\BillingAccount;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Repositories\Entitlement\EntitlementSnapshotRepository;
use App\Repositories\Subscription\SubscriptionRepository;
use Illuminate\Support\Facades\Log;

class RecomputeEntitlementsAction
{
    public function __construct(
        private EntitlementSnapshotRepository $snapshotRepository,
        private SubscriptionRepository $subscriptionRepository,
    ) {}

    public function execute(RecomputeEntitlementsDTO $dto): StoreEntitlementSnapshot
    {
        $billingAccount = BillingAccount::findOrFail($dto->billingAccountId);
        Store::findOrFail($dto->storeId); // fail fast

        $subscription = $this->subscriptionRepository->getActiveForAccount($dto->billingAccountId);

        if ($dto->isGrandfathered) {
            return $this->createGrandfatheredSnapshot($dto, $subscription, $billingAccount);
        }

        if (!$subscription) {
            // No subscription: EnsureActiveSubscription/entitlement_status=NONE blocks write access
            return $this->snapshotRepository->upsert($dto->storeId, [
                'billing_account_id' => $dto->billingAccountId,
                'subscription_id' => null,
                'plan_id' => null,
                'entitlement_status' => EntitlementStatusEnum::NONE->value,
                'features' => [],
                'expires_at' => null,
            ]);
        }

        $entitlementStatus = EntitlementStatusEnum::fromSubscriptionStatus($subscription->status);
        $features = $this->materializeFeatures($subscription->plan);

        // stores.max is account-scoped (billing_accounts.stores_count),
        // not store-scoped. Store it there, remove from snapshot to prevent
        // duplication and stale reads.
        $this->syncAccountScopedLimits($billingAccount, $features);
        unset($features[FeatureKeyEnum::STORES_MAX->value]);

        $expiresAt = match ($subscription->status->value) {
            'trialing' => $subscription->trial_ends_at,
            'active', 'canceled' => $subscription->current_period_ends_at,
            'past_due', 'grace_period' => $subscription->grace_period_ends_at,
            default => null,
        };

        $snapshot = $this->snapshotRepository->upsert($dto->storeId, [
            'billing_account_id' => $dto->billingAccountId,
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'entitlement_status' => $entitlementStatus->value,
            'features' => $features,
            'expires_at' => $expiresAt,
        ]);

        Log::channel('billing')->info('entitlements.recomputed', [
            'store_id' => $dto->storeId,
            'billing_account_id' => $dto->billingAccountId,
            'subscription_id' => $subscription->id,
            'entitlement_status' => $entitlementStatus->value,
            'expires_at' => $expiresAt?->toDateTimeString(),
        ]);

        return $snapshot;
    }

    private function materializeFeatures($plan): array
    {
        if (!$plan) {
            // CRITICAL: A missing plan should never happen after the guards in DeletePlanAction
            // are in place. If we reach this, it's a critical error, not a silent degradation.
            \Illuminate\Support\Facades\Log::channel('billing')->error(
                'entitlements.missing_plan',
                [
                    'message' => 'Attempted to materialize features for null plan',
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                ]
            );
            
            throw new \DomainException(
                'Cannot compute entitlements: plan is missing. This indicates a data integrity issue.'
            );
        }

        if (!$plan->relationLoaded('features')) {
            $plan = $plan->load('features');
        }

        $features = [];
        foreach ($plan->features as $feature) {
            $key = $feature->feature_key->value;
            $features[$key] = match ($feature->value_type) {
                'limit' => $feature->limit_value,
                'unlimited' => null,
                'boolean' => $feature->boolean_value,
                default => null,
            };
        }

        return $features;
    }

    /**
     * Single source of truth for account-scoped limits.
     * Currently: stores.max only.
     */
    private function syncAccountScopedLimits(BillingAccount $billingAccount, array $features): void
    {
        $storesMax = array_key_exists(FeatureKeyEnum::STORES_MAX->value, $features)
            ? $features[FeatureKeyEnum::STORES_MAX->value]
            : null;

        if ($billingAccount->stores_max !== $storesMax) {
            $billingAccount->update(['stores_max' => $storesMax]);
        }
    }

    private function createGrandfatheredSnapshot(
        RecomputeEntitlementsDTO $dto,
        $subscription,
        BillingAccount $billingAccount
    ): StoreEntitlementSnapshot {
        $entitlementStatus = EntitlementStatusEnum::GRANDFATHERED;

        if ($subscription && $subscription->plan) {
            $features = $this->materializeFeatures($subscription->plan);
            $planId = $subscription->plan_id;
            $subscriptionId = $subscription->id;
            $expiresAt = $subscription->current_period_ends_at;
        } else {
            $features = $this->getDefaultStarterFeatures();
            $planId = null;
            $subscriptionId = null;
            $expiresAt = null;
        }

        $this->syncAccountScopedLimits($billingAccount, $features);
        unset($features[FeatureKeyEnum::STORES_MAX->value]);

        $snapshot = $this->snapshotRepository->upsert($dto->storeId, [
            'billing_account_id' => $dto->billingAccountId,
            'subscription_id' => $subscriptionId,
            'plan_id' => $planId,
            'entitlement_status' => $entitlementStatus->value,
            'features' => $features,
            'expires_at' => $expiresAt,
        ]);

        Log::channel('billing')->info('entitlements.grandfathered', [
            'store_id' => $dto->storeId,
            'billing_account_id' => $dto->billingAccountId,
            'subscription_id' => $subscriptionId,
            'entitlement_status' => $entitlementStatus->value,
            'expires_at' => $expiresAt?->toDateTimeString(),
        ]);

        return $snapshot;
    }

    private function getDefaultStarterFeatures(): array
    {
        return [
            FeatureKeyEnum::PRODUCTS_MAX->value => 1000,
            FeatureKeyEnum::STORES_MAX->value => 1,
            FeatureKeyEnum::ANALYTICS_ADVANCED->value => false,
            FeatureKeyEnum::API_ACCESS->value => false,
            FeatureKeyEnum::PRIORITY_SUPPORT->value => false,
        ];
    }
}
