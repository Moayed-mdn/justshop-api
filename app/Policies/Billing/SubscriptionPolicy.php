<?php

declare(strict_types=1);

namespace App\Policies\Billing;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\BillingAccount;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class SubscriptionPolicy
{
    use HasStoreMembership;

    public function view(User $user, BillingAccount $billingAccount): bool
    {
        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::SUBSCRIPTION_VIEW)) {
            return $this->decision($user, 'view', true, $billingAccount);
        }

        $this->denyWithContext('subscription', 'view', PermissionEnum::SUBSCRIPTION_VIEW);
    }

    public function viewUsage(User $user, BillingAccount $billingAccount): bool
    {
        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::SUBSCRIPTION_VIEW)) {
            return $this->decision($user, 'viewUsage', true, $billingAccount);
        }

        $this->denyWithContext('subscription', 'view', PermissionEnum::SUBSCRIPTION_VIEW);
    }

    public function upgrade(User $user, BillingAccount $billingAccount, Store $store): bool
    {
        if ($this->isMember($user, $store) && $user->can(PermissionEnum::SUBSCRIPTION_UPGRADE)) {
            return $this->decision($user, 'upgrade', true, $billingAccount, ['store_id' => $store->id]);
        }

        if ($this->isMember($user, $store)) {
            $this->denyWithContext('subscription', 'upgrade', PermissionEnum::SUBSCRIPTION_UPGRADE);
        }

        $this->denyWithContext('subscription', 'upgrade', PermissionEnum::SUBSCRIPTION_UPGRADE);
    }

    /**
     * Check if user can move to current plan version (no store context needed).
     * This is similar to upgrade but doesn't require a specific store context
     * since it's a version upgrade within the same tier.
     */
    public function moveToCurrentVersion(User $user, BillingAccount $billingAccount): bool
    {
        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::SUBSCRIPTION_UPGRADE)) {
            return $this->decision($user, 'moveToCurrentVersion', true, $billingAccount);
        }

        $this->denyWithContext('subscription', 'upgrade', PermissionEnum::SUBSCRIPTION_UPGRADE);
    }

    public function downgrade(User $user, BillingAccount $billingAccount, Store $store): bool
    {
        if ($this->isMember($user, $store) && $user->can(PermissionEnum::SUBSCRIPTION_DOWNGRADE)) {
            return $this->decision($user, 'downgrade', true, $billingAccount, ['store_id' => $store->id]);
        }

        if ($this->isMember($user, $store)) {
            $this->denyWithContext('subscription', 'downgrade', PermissionEnum::SUBSCRIPTION_DOWNGRADE);
        }

        $this->denyWithContext('subscription', 'downgrade', PermissionEnum::SUBSCRIPTION_DOWNGRADE);
    }

    public function cancel(User $user, BillingAccount $billingAccount): bool
    {
        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::SUBSCRIPTION_CANCEL)) {
            return $this->decision($user, 'cancel', true, $billingAccount);
        }

        $this->denyWithContext('subscription', 'cancel', PermissionEnum::SUBSCRIPTION_CANCEL);
    }

    public function resume(User $user, BillingAccount $billingAccount): bool
    {
        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::SUBSCRIPTION_RESUME)) {
            return $this->decision($user, 'resume', true, $billingAccount);
        }

        $this->denyWithContext('subscription', 'resume', PermissionEnum::SUBSCRIPTION_RESUME);
    }

    private function isOwnerOrLinkedMember(User $user, BillingAccount $billingAccount, string $permission): bool
    {
        if (!$user->can($permission)) {
            return false;
        }

        if ($user->id === $billingAccount->owner_user_id) {
            return true;
        }

        $isLinkedStoreMember = StoreEntitlementSnapshot::where('billing_account_id', $billingAccount->id)
            ->whereHas('store', function ($q) use ($user) {
                $q->whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->exists();

        if ($isLinkedStoreMember) {
            return true;
        }

        // Super admins are never implicitly linked to a billing account.
        // Access requires a governed, audited impersonation session AND the
        // super admin's own explicit permission grant checked above -- there
        // is no blanket bypass.
        return $this->isGovernedImpersonationActive($user);
    }
}
