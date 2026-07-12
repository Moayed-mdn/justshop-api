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
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::SUBSCRIPTION_VIEW)) {
            return $this->decision($user, 'view', true, $billingAccount);
        }

        return $this->decision($user, 'view', false, $billingAccount);
    }

    public function viewUsage(User $user, BillingAccount $billingAccount): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::SUBSCRIPTION_VIEW)) {
            return $this->decision($user, 'viewUsage', true, $billingAccount);
        }

        return $this->decision($user, 'viewUsage', false, $billingAccount);
    }

    public function upgrade(User $user, BillingAccount $billingAccount, Store $store): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if ($this->isMember($user, $store) && $user->can(PermissionEnum::SUBSCRIPTION_UPGRADE)) {
            return $this->decision($user, 'upgrade', true, $billingAccount, ['store_id' => $store->id]);
        }

        if ($this->isMember($user, $store)) {
            $this->denyWithContext('subscription', 'upgrade', PermissionEnum::SUBSCRIPTION_UPGRADE);
        }

        return $this->decision($user, 'upgrade', false, $billingAccount);
    }

    public function downgrade(User $user, BillingAccount $billingAccount, Store $store): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if ($this->isMember($user, $store) && $user->can(PermissionEnum::SUBSCRIPTION_DOWNGRADE)) {
            return $this->decision($user, 'downgrade', true, $billingAccount, ['store_id' => $store->id]);
        }

        if ($this->isMember($user, $store)) {
            $this->denyWithContext('subscription', 'downgrade', PermissionEnum::SUBSCRIPTION_DOWNGRADE);
        }

        return $this->decision($user, 'downgrade', false, $billingAccount);
    }

    public function cancel(User $user, BillingAccount $billingAccount): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::SUBSCRIPTION_CANCEL)) {
            return $this->decision($user, 'cancel', true, $billingAccount);
        }

        return $this->decision($user, 'cancel', false, $billingAccount);
    }

    public function resume(User $user, BillingAccount $billingAccount): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::SUBSCRIPTION_RESUME)) {
            return $this->decision($user, 'resume', true, $billingAccount);
        }

        return $this->decision($user, 'resume', false, $billingAccount);
    }

    private function isOwnerOrLinkedMember(User $user, BillingAccount $billingAccount, string $permission): bool
    {
        if ($user->id === $billingAccount->owner_user_id) {
            return true;
        }

        if (!$user->can($permission)) {
            return false;
        }

        return StoreEntitlementSnapshot::where('billing_account_id', $billingAccount->id)
            ->whereHas('store', function ($q) use ($user) {
                $q->whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->exists();
    }
}
