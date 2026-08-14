<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;
use Illuminate\Auth\Access\Response;

/**
 * Platform Order Policy
 * 
 * Governs platform-level order access.
 * Platform authority is INDEPENDENT from merchant authority.
 * 
 * Platform actors operate at platform level across all stores.
 * Platform permissions are separate from merchant permissions.
 */
class PlatformOrderPolicy
{
    use InteractsWithPolicyTelemetry;

    /**
     * Determine if the user can view any platform orders.
     */
    public function viewAny(User $user): Response
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_VIEW);

        $this->decision($user, 'viewAny', $hasPermission, 'platform_orders');

        return $hasPermission
            ? Response::allow()
            : Response::deny(__('error.permission.platform_order.view'));
    }

    /**
     * Determine if the user can view a specific platform order.
     * 
     * Platform actors can view orders from any store when authorized.
     * This is intentionally cross-store.
     */
    public function view(User $user, Order $order): Response
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_VIEW);

        $this->decision($user, 'view', $hasPermission, $order);

        return $hasPermission
            ? Response::allow()
            : Response::deny(__('error.permission.platform_order.view'));
    }

    /**
     * Determine if the user can update order status at platform level.
     */
    public function updateStatus(User $user, Order $order): Response
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_UPDATE_STATUS);

        $this->decision($user, 'updateStatus', $hasPermission, $order);

        return $hasPermission
            ? Response::allow()
            : Response::deny(__('error.permission.platform_order.update_status'));
    }

    /**
     * Determine if the user can cancel orders at platform level.
     */
    public function cancel(User $user, Order $order): Response
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_CANCEL);

        $this->decision($user, 'cancel', $hasPermission, $order);

        return $hasPermission
            ? Response::allow()
            : Response::deny(__('error.permission.platform_order.cancel'));
    }

    /**
     * Determine if the user can refund orders at platform level.
     */
    public function refund(User $user, Order $order): Response
    {
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ORDER_REFUND);

        $this->decision($user, 'refund', $hasPermission, $order);

        return $hasPermission
            ? Response::allow()
            : Response::deny(__('error.permission.platform_order.refund'));
    }
}
