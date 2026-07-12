<?php

namespace App\Repositories\Billing;

use App\Models\BillingAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BillingAccountRepository
{
    /**
     * Find billing account by ID.
     */
    public function findById(int $billingAccountId): ?BillingAccount
    {
        return BillingAccount::find($billingAccountId);
    }

    /**
     * Find billing account by ID or fail.
     */
    public function findByIdOrFail(int $billingAccountId): BillingAccount
    {
        return BillingAccount::findOrFail($billingAccountId);
    }

    /**
     * Find billing account by owner user ID.
     */
    public function findByOwner(int $ownerUserId): ?BillingAccount
    {
        return BillingAccount::where('owner_user_id', $ownerUserId)->first();
    }

    /**
     * Find billing account by owner user ID or fail.
     */
    public function findByOwnerOrFail(int $ownerUserId): BillingAccount
    {
        $account = $this->findByOwner($ownerUserId);

        if (!$account) {
            throw new ModelNotFoundException(
                "Billing account not found for user ID: {$ownerUserId}"
            );
        }

        return $account;
    }

    /**
     * Find billing account accessible by the user.
     *
     * Checks direct ownership first, then falls back to stores
     * the user is a member of that are linked to a billing account.
     */
    public function findByUserAccess(User $user): ?BillingAccount
    {
        $account = $this->findByOwner($user->id);

        if ($account) {
            return $account;
        }

        $storeIds = $user->stores()->pluck('stores.id');

        if ($storeIds->isEmpty()) {
            return null;
        }

        return BillingAccount::whereHas('entitlementSnapshots', function ($q) use ($storeIds) {
            $q->whereIn('store_id', $storeIds);
        })->first();
    }

    /**
     * Create a new billing account.
     */
    public function create(array $data): BillingAccount
    {
        return BillingAccount::create($data);
    }

    /**
     * Update a billing account.
     */
    public function update(BillingAccount $account, array $data): BillingAccount
    {
        $account->update($data);
        return $account->fresh();
    }

    /**
     * Get or create billing account for a user.
     */
    public function getOrCreate(User $user, array $defaults = []): BillingAccount
    {
        return BillingAccount::firstOrCreate(
            ['owner_user_id' => $user->id],
            array_merge([
                'billing_email'    => $user->email,
                'default_currency' => 'USD',
                'status'           => 'active',
            ], $defaults)
        );
    }

    /**
     * Mark trial as used for a billing account.
     */
    public function markTrialAsUsed(BillingAccount $account): void
    {
        $account->update(['trial_used' => true]);
    }

    /**
     * Check if user has used trial.
     */
    public function hasUsedTrial(int $ownerUserId): bool
    {
        $account = $this->findByOwner($ownerUserId);
        return $account && $account->trial_used;
    }

    /**
     * Get all active billing accounts.
     */
    public function getAllActive()
    {
        return BillingAccount::active()->get();
    }
}
