<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Exceptions\Authorization\PermissionDeniedException;
use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * Platform Order Policy
 *
 * Governs platform-level order access.
 * Platform authority is INDEPENDENT from merchant authority, and is
 * INDEPENDENT from the SUPER_ADMIN role itself: holding the SUPER_ADMIN
 * role grants no implicit access here. Access requires the explicit,
 * separately-grantable PLATFORM_ORDER_* permissions below -- this is the
 * "explicit ability for platform-level operations" pattern, not a role
 * bypass. A platform actor who has not been granted these permissions is
 * denied exactly like anyone else.
 *
 * Platform actors (once granted PLATFORM_ORDER_VIEW etc.) operate across
 * all stores by design -- this is intentionally cross-store, since it is
 * a genuine platform-level ability rather than tenant-owned access.
 */
class PlatformOrderPolicy
{
    use InteractsWithPolicyTelemetry;

    /**
     * Determine if the user can view any platform orders.
     */
    public function viewAny(User $user): bool
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_VIEW);

        $this->decision($user, 'viewAny', $hasPermission, 'platform_orders');

        if ($hasPermission) {
            return true;
        }

        $this->denyWithContext('platform_order', 'view', PermissionEnum::PLATFORM_ORDER_VIEW);
    }

    /**
     * Determine if the user can view a specific platform order.
     *
     * Platform actors can view orders from any store when authorized.
     * This is intentionally cross-store.
     */
    public function view(User $user, Order $order): bool
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_VIEW);

        $this->decision($user, 'view', $hasPermission, $order);

        if ($hasPermission) {
            return true;
        }

        $this->denyWithContext('platform_order', 'view', PermissionEnum::PLATFORM_ORDER_VIEW);
    }

    /**
     * Determine if the user can update order status at platform level.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_UPDATE_STATUS);

        $this->decision($user, 'updateStatus', $hasPermission, $order);

        if ($hasPermission) {
            return true;
        }

        $this->denyWithContext('platform_order', 'update_status', PermissionEnum::PLATFORM_ORDER_UPDATE_STATUS);
    }

    /**
     * Determine if the user can cancel orders at platform level.
     */
    public function cancel(User $user, Order $order): bool
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_CANCEL);

        $this->decision($user, 'cancel', $hasPermission, $order);

        if ($hasPermission) {
            return true;
        }

        $this->denyWithContext('platform_order', 'cancel', PermissionEnum::PLATFORM_ORDER_CANCEL);
    }

    /**
     * Determine if the user can refund orders at platform level.
     */
    public function refund(User $user, Order $order): bool
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_REFUND);

        $this->decision($user, 'refund', $hasPermission, $order);

        if ($hasPermission) {
            return true;
        }

        $this->denyWithContext('platform_order', 'refund', PermissionEnum::PLATFORM_ORDER_REFUND);
    }

    private function denyWithContext(string $resource, string $action, ?string $permission = null): never
    {
        throw new PermissionDeniedException($resource, $action, $permission);
    }
}
