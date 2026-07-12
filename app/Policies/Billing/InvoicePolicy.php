<?php

declare(strict_types=1);

namespace App\Policies\Billing;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\BillingAccount;
use App\Models\Invoice;
use App\Models\StoreEntitlementSnapshot;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class InvoicePolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, BillingAccount $billingAccount): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::INVOICE_VIEW)) {
            return $this->decision($user, 'viewAny', true, $billingAccount);
        }

        return $this->decision($user, 'viewAny', false, $billingAccount);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $billingAccount = $invoice->billingAccount;

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::INVOICE_VIEW)) {
            return $this->decision($user, 'view', true, $invoice);
        }

        return $this->decision($user, 'view', false, $invoice);
    }

    public function download(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $billingAccount = $invoice->billingAccount;

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::INVOICE_DOWNLOAD)) {
            return $this->decision($user, 'download', true, $invoice);
        }

        return $this->decision($user, 'download', false, $invoice);
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
