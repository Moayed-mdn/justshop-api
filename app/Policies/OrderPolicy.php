<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class OrderPolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->canView($user, $store), $store);
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->id === $order->user_id) {
            return $this->decision($user, 'view', true, $order);
        }

        if ($this->isMerchant($user) && $this->isMember($user, $order->store)) {
            if ($user->can(PermissionEnum::ORDER_VIEW)) {
                return $this->decision($user, 'view', true, $order);
            }
            $this->denyWithContext('order', 'view', PermissionEnum::ORDER_VIEW);
        }

        return $this->decision($user, 'view', false, $order);
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $this->decision(
            $user,
            'updateStatus',
            $this->canManage($user, $order->store, PermissionEnum::ORDER_UPDATE_STATUS, 'order', 'update_status'),
            $order,
        );
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->id === $order->user_id) {
            return $this->decision($user, 'cancel', true, $order);
        }

        if ($this->isMerchant($user) && $this->isMember($user, $order->store)) {
            if ($user->can(PermissionEnum::ORDER_CANCEL)) {
                return $this->decision($user, 'cancel', true, $order);
            }
            $this->denyWithContext('order', 'cancel', PermissionEnum::ORDER_CANCEL);
        }

        return $this->decision($user, 'cancel', false, $order);
    }

    public function refund(User $user, Order $order): bool
    {
        return $this->decision(
            $user,
            'refund',
            $this->canManage($user, $order->store, PermissionEnum::ORDER_REFUND, 'order', 'refund'),
            $order,
        );
    }

    private function canView(User $user, Store $store): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can(PermissionEnum::ORDER_VIEW);

        if ($isAdmin) {
            return $hasPermission;
        }

        if ($this->isMember($user, $store)) {
            if ($hasPermission) {
                return true;
            }
            $this->denyWithContext('order', 'view', PermissionEnum::ORDER_VIEW);
        }

        return false;
    }

    private function canManage(User $user, Store $store, string $permission, string $resource, string $action): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can($permission);

        if ($isAdmin) {
            return $hasPermission;
        }

        if ($this->isMember($user, $store)) {
            if ($hasPermission) {
                return true;
            }
            $this->denyWithContext($resource, $action, $permission);
        }

        return false;
    }
}
