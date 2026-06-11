<?php

namespace App\Actions\Entitlement;

use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Models\BillingAccount;
use App\Models\Product;
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

    /**
     * Recompute and materialize entitlements for a store.
     * 
     * This creates/updates the store_entitlement_snapshots record with:
     * - Current subscription status
     * - Plan features (limits and boolean flags)
     * - Current usage counts
     * - Access expiry timestamp
     */
    public function execute(RecomputeEntitlementsDTO $dto): StoreEntitlementSnapshot
    {
        $billingAccount = BillingAccount::findOrFail($dto->billingAccountId);
        $store = Store::findOrFail($dto->storeId);

        // Get active subscription for this billing account
        $subscription = $this->subscriptionRepository->getActiveForAccount($dto->billingAccountId);

        // Handle grandfathered stores
        if ($dto->isGrandfathered) {
            return $this->createGrandfatheredSnapshot($dto, $subscription, $billingAccount);
        }

        // If no subscription, set to NONE status
        if (!$subscription) {
            return $this->snapshotRepository->upsert($dto->storeId, [
                'billing_account_id' => $dto->billingAccountId,
                'subscription_id' => null,
                'plan_id' => null,
                'entitlement_status' => EntitlementStatusEnum::NONE->value,
                'features' => [],
                'limits' => $this->getCurrentUsageCounts($dto->storeId, $billingAccount->owner_user_id),
                'expires_at' => null,
            ]);
        }

        // Derive entitlement status from subscription status
        $entitlementStatus = EntitlementStatusEnum::fromSubscriptionStatus($subscription->status);

        // Materialize features from plan_features
        $features = $this->materializeFeatures($subscription->plan);

        // Get current usage counts
        $limits = $this->getCurrentUsageCounts($dto->storeId, $billingAccount->owner_user_id);

        // Determine access expiry
        $expiresAt = match ($subscription->status->value) {
            'trialing' => $subscription->trial_ends_at,
            'active', 'canceled' => $subscription->current_period_ends_at,
            'past_due', 'grace_period' => $subscription->grace_period_ends_at,
            default => null,
        };

        // Upsert snapshot
        $snapshot = $this->snapshotRepository->upsert($dto->storeId, [
            'billing_account_id' => $dto->billingAccountId,
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'entitlement_status' => $entitlementStatus->value,
            'features' => $features,
            'limits' => $limits,
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

    /**
     * Materialize plan features into a flat array structure.
     * 
     * Returns format:
     * [
     *     'products.max' => 1000,
     *     'stores.max' => 1,
     *     'analytics.advanced' => false,
     *     'support.priority' => false,
     * ]
     */
    private function materializeFeatures($plan): array
    {
        if (!$plan || !$plan->relationLoaded('features')) {
            $plan = $plan->load('features');
        }

        $features = [];

        foreach ($plan->features as $feature) {
            $key = $feature->feature_key->value;

            $features[$key] = match ($feature->value_type) {
                'limit' => $feature->limit_value,
                'unlimited' => null, // null = unlimited
                'boolean' => $feature->boolean_value,
                default => null,
            };
        }

        return $features;
    }

    /**
     * Get current usage counts for limits enforcement.
     * 
     * Returns format:
     * [
     *     'products.count' => 42,
     *     'stores.count' => 1,
     * ]
     */
    private function getCurrentUsageCounts(int $storeId, int $ownerUserId): array
    {
        // Products count for this store
        $productsCount = Product::where('store_id', $storeId)->count();

        // Stores count for this owner (across all stores)
        $storesCount = Store::where('owner_id', $ownerUserId)->count();

        return [
            'products.count' => $productsCount,
            'stores.count' => $storesCount,
        ];
    }

    /**
     * Create entitlement snapshot for grandfathered store.
     */
    private function createGrandfatheredSnapshot(
        RecomputeEntitlementsDTO $dto,
        $subscription,
        BillingAccount $billingAccount
    ): StoreEntitlementSnapshot {
        // Grandfathered stores get GRANDFATHERED status with full access
        $entitlementStatus = EntitlementStatusEnum::GRANDFATHERED;

        // Use subscription plan if exists, otherwise use default starter features
        if ($subscription && $subscription->plan) {
            $features = $this->materializeFeatures($subscription->plan);
            $planId = $subscription->plan_id;
            $subscriptionId = $subscription->id;
            $expiresAt = $subscription->current_period_ends_at;
        } else {
            // Default to starter plan features for grandfathered accounts
            $features = $this->getDefaultStarterFeatures();
            $planId = null;
            $subscriptionId = null;
            $expiresAt = null;
        }

        // Get current usage counts
        $limits = $this->getCurrentUsageCounts($dto->storeId, $billingAccount->owner_user_id);

        // Upsert snapshot
        $snapshot = $this->snapshotRepository->upsert($dto->storeId, [
            'billing_account_id' => $dto->billingAccountId,
            'subscription_id' => $subscriptionId,
            'plan_id' => $planId,
            'entitlement_status' => $entitlementStatus->value,
            'features' => $features,
            'limits' => $limits,
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

    /**
     * Get default starter features for grandfathered accounts without a plan.
     */
    private function getDefaultStarterFeatures(): array
    {
        return [
            'products.max' => 1000,
            'stores.max' => 1,
            'analytics.advanced' => false,
            'api.access' => false,
            'support.priority' => false,
        ];
    }
}
