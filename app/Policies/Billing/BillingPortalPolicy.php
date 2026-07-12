<?php

declare(strict_types=1);

namespace App\Policies\Billing;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\BillingAccount;
use App\Models\StoreEntitlementSnapshot;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class BillingPortalPolicy
{
    use HasStoreMembership;

    public function createSession(User $user, BillingAccount $billingAccount): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::BILLING_PORTAL)) {
            return $this->decision($user, 'createSession', true, $billingAccount);
        }

        return $this->decision($user, 'createSession', false, $billingAccount);
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
