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
        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::INVOICE_VIEW)) {
            return $this->decision($user, 'viewAny', true, $billingAccount);
        }

        $this->denyWithContext('invoice', 'view', PermissionEnum::INVOICE_VIEW);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        $billingAccount = $invoice->billingAccount;

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::INVOICE_VIEW)) {
            return $this->decision($user, 'view', true, $invoice);
        }

        $this->denyWithContext('invoice', 'view', PermissionEnum::INVOICE_VIEW);
    }

    public function download(User $user, Invoice $invoice): bool
    {
        $billingAccount = $invoice->billingAccount;

        if ($this->isOwnerOrLinkedMember($user, $billingAccount, PermissionEnum::INVOICE_DOWNLOAD)) {
            return $this->decision($user, 'download', true, $invoice);
        }

        $this->denyWithContext('invoice', 'download', PermissionEnum::INVOICE_DOWNLOAD);
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
