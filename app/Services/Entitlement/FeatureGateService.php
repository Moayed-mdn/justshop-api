<?php

namespace App\Services\Entitlement;

use App\Enums\Entitlement\FeatureKeyEnum;
use App\Exceptions\Entitlement\FeatureNotAvailableException;
use App\Exceptions\Entitlement\QuotaExceededException;
use App\Exceptions\Subscription\SubscriptionRequiredException;
use App\Models\StoreEntitlementSnapshot;
use App\Repositories\Billing\BillingAccountRepository;
use App\Repositories\Entitlement\EntitlementSnapshotRepository;

class FeatureGateService
{
    public function __construct(
        private EntitlementSnapshotRepository $snapshotRepository,
        private BillingAccountRepository $billingAccountRepository,
    ) {}

    /**
     * Ensure the store has write access (full operational access).
     * 
     * Throws exception if access is blocked (past_due, grace_period, expired, etc.)
     * 
     * @throws SubscriptionRequiredException
     */
    public function ensureWriteAccess(int $storeId): void
    {
        $snapshot = $this->snapshotRepository->findByStoreId($storeId);

        // No snapshot = no subscription = no access
        if (!$snapshot) {
            throw new SubscriptionRequiredException(
                __('entitlement.subscription_required')
            );
        }

        // Check if entitlement status grants write access
        if (!$snapshot->entitlement_status->grantsWriteAccess()) {
            throw new SubscriptionRequiredException(
                __('entitlement.subscription_status_restricted', ['status' => $snapshot->entitlement_status->value])
            );
        }
    }

    /**
     * Ensure the store has read access.
     * 
     * Less strict than write access - allows past_due and grace_period states.
     * 
     * @throws SubscriptionRequiredException
     */
    public function ensureReadAccess(int $storeId): void
    {
        $snapshot = $this->snapshotRepository->findByStoreId($storeId);

        if (!$snapshot) {
            throw new SubscriptionRequiredException(
                'An active subscription is required to access this store.'
            );
        }

        if (!$snapshot->entitlement_status->grantsReadAccess()) {
            throw new SubscriptionRequiredException(
                "Store access is restricted. Current status: {$snapshot->entitlement_status->value}"
            );
        }
    }

    /**
     * Ensure quota/limit is not exceeded for a store-scoped feature.
     * 
     * @throws QuotaExceededException
     */
    public function ensureQuota(int $storeId, FeatureKeyEnum $feature): void
    {
        $snapshot = $this->snapshotRepository->findByStoreId($storeId);

        if (!$snapshot) {
            throw new SubscriptionRequiredException('An active subscription is required.');
        }

        $limit = $snapshot->features[$feature->value] ?? null;
        if ($limit === null) {
            return;
        }

        $currentCount = match ($feature) {
            FeatureKeyEnum::PRODUCTS_MAX => $snapshot->products_count,
            default => throw new \LogicException("No store-scoped counter mapped for {$feature->value}"),
        };

        if ($currentCount >= $limit) {
            throw new QuotaExceededException(featureKey: $feature->value, limit: $limit);
        }
    }

    /**
     * Ensure quota/limit is not exceeded for an account-scoped feature.
     * 
     * @throws QuotaExceededException
     */
    public function ensureAccountQuota(int $ownerUserId, FeatureKeyEnum $feature): void
    {
        $account = $this->billingAccountRepository->findByOwner($ownerUserId);

        if (!$account) {
            return; // No account yet = first resource = allowed
        }

        $limit = match ($feature) {
            FeatureKeyEnum::STORES_MAX => $account->stores_max,
            default => throw new \LogicException("No account-scoped limit mapped for {$feature->value}"),
        };

        if ($limit === null) {
            return;
        }

        if ($account->stores_count >= $limit) {
            throw new QuotaExceededException(featureKey: $feature->value, limit: $limit);
        }
    }

    /**
     * Check if a feature is available for this store.
     * 
     * Returns true if feature exists and is enabled, false otherwise.
     */
    public function hasFeature(int $storeId, FeatureKeyEnum $feature): bool
    {
        $snapshot = $this->snapshotRepository->findByStoreId($storeId);

        if (!$snapshot) {
            return false;
        }

        $featureKey = $feature->value;
        $featureValue = $snapshot->features[$featureKey] ?? null;

        // For boolean features, check if true
        if (is_bool($featureValue)) {
            return $featureValue === true;
        }

        // For limit features, check if exists (not null)
        return $featureValue !== null;
    }

    /**
     * Get the limit value for a feature.
     * 
     * Returns null if unlimited or feature doesn't exist.
     */
    public function getFeatureLimit(int $storeId, FeatureKeyEnum $feature): ?int
    {
        $snapshot = $this->snapshotRepository->findByStoreId($storeId);

        if (!$snapshot) {
            return null;
        }

        $featureKey = $feature->value;
        $featureValue = $snapshot->features[$featureKey] ?? null;

        // Return limit if it's a number, null if unlimited or doesn't exist
        return is_numeric($featureValue) ? (int) $featureValue : null;
    }

    /**
     * Get the entitlement snapshot for a store.
     */
    public function getSnapshot(int $storeId): ?StoreEntitlementSnapshot
    {
        return $this->snapshotRepository->findByStoreId($storeId);
    }
}
