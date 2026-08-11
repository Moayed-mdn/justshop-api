<?php

namespace App\Repositories\Subscription;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\BillingAccount;
use App\Models\Subscription;
use Illuminate\Support\Collection;

class SubscriptionRepository
{
    /**
     * Find subscription by ID.
     */
    public function findById(int $subscriptionId): ?Subscription
    {
        return Subscription::find($subscriptionId);
    }

    /**
     * Find subscription by ID or fail.
     */
    public function findByIdOrFail(int $subscriptionId): Subscription
    {
        return Subscription::findOrFail($subscriptionId);
    }

    /**
     * Get active subscription for a billing account.
     */
    public function getActiveForAccount(int $billingAccountId): ?Subscription
    {
        return Subscription::where('billing_account_id', $billingAccountId)
            ->withAccess()
            ->latest()
            ->first();
    }

    /**
     * Get the latest incomplete subscription for a billing account.
     * Used to detect pending/abandoned checkout sessions.
     */
    public function getLatestIncompleteForAccount(int $billingAccountId): ?Subscription
    {
        return Subscription::where('billing_account_id', $billingAccountId)
            ->where('status', 'incomplete')
            ->latest()
            ->first();
    }

    /**
     * Find active subscription for a billing account or fail.
     */
    public function findActiveForAccountOrFail(int $billingAccountId): Subscription
    {
        $subscription = $this->getActiveForAccount($billingAccountId);

        if (!$subscription) {
            throw new \DomainException('No active subscription found for this account.');
        }

        return $subscription;
    }

    /**
     * Find active subscription for a billing account with row lock.
     * 
     * Use this for operations that modify the subscription (upgrade, downgrade)
     * to prevent race conditions from concurrent requests.
     */
    public function findActiveForAccountOrFailWithLock(int $billingAccountId): Subscription
    {
        $subscription = Subscription::where('billing_account_id', $billingAccountId)
            ->withAccess()
            ->lockForUpdate()
            ->latest()
            ->first();

        if (!$subscription) {
            throw new \DomainException('No active subscription found for this account.');
        }

        return $subscription;
    }

    /**
     * Find active subscription for a billing account.
     */
    public function findActiveForAccount(int $billingAccountId): ?Subscription
    {
        return $this->getActiveForAccount($billingAccountId);
    }

    /**
     * Create a new subscription.
     * 
     * CRITICAL: Enforces one active subscription per billing account.
     */
    public function create(array $data): Subscription
    {
        // Enforce one active subscription per billing account (MySQL doesn't support partial unique index)
        $billingAccountId = $data['billing_account_id'];
        $status = $data['status'] ?? null;

        $activeStates = [
            SubscriptionStatusEnum::TRIALING->value,
            SubscriptionStatusEnum::ACTIVE->value,
            SubscriptionStatusEnum::PAST_DUE->value,
            SubscriptionStatusEnum::GRACE_PERIOD->value,
            SubscriptionStatusEnum::PAUSED->value,
        ];

        if ($status && in_array($status, $activeStates)) {
            $existingActive = Subscription::where('billing_account_id', $billingAccountId)
                ->whereIn('status', $activeStates)
                ->whereNull('deleted_at')
                ->exists();

            if ($existingActive) {
                throw new \RuntimeException(
                    "Billing account {$billingAccountId} already has an active subscription"
                );
            }
        }

        return Subscription::create($data);
    }

    /**
     * Update a subscription.
     */
    public function update(Subscription $subscription, array $data): Subscription
    {
        $subscription->update($data);
        return $subscription->fresh();
    }

    /**
     * Get all subscriptions for a billing account.
     */
    public function getAllForAccount(int $billingAccountId): Collection
    {
        return Subscription::where('billing_account_id', $billingAccountId)
            ->latest()
            ->get();
    }

    /**
     * Get expiring trials.
     */
    public function getExpiringTrials(int $daysThreshold = 3): Collection
    {
        return Subscription::expiringTrials($daysThreshold)->get();
    }

    /**
     * Get expired trials.
     */
    public function getExpiredTrials(): Collection
    {
        return Subscription::expiredTrials()->get();
    }

    /**
     * Get expired grace periods.
     */
    public function getExpiredGracePeriods(): Collection
    {
        return Subscription::expiredGracePeriods()->get();
    }

    /**
     * Find subscription by provider subscription ID.
     */
    public function findByProviderSubscriptionId(
        string $providerSubscriptionId,
        string $provider = 'stripe'
    ): ?Subscription {
        return Subscription::where('provider', $provider)
            ->where('provider_subscription_id', $providerSubscriptionId)
            ->first();
    }

    /**
     * Get subscriptions with pending plan changes.
     */
    public function getWithPendingPlanChanges(): Collection
    {
        return Subscription::whereNotNull('pending_plan_id')
            ->where('pending_plan_effective_at', '<=', now())
            ->get();
    }

    /**
     * Soft delete a subscription.
     */
    public function delete(Subscription $subscription): bool
    {
        return $subscription->delete();
    }
}
