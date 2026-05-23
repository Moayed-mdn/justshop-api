<?php
// app/Policies/OrderPolicy.php
namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class OrderPolicy
{
    use HasStoreMembership;

    public function view(User $user, Order $order): bool
    {
        // Rule 1: Owners can always view their own orders
        if ($user->id === $order->user_id) {
            return $this->decision($user, 'view', true, $order);
        }

        // Rule 2: Merchants can view orders for their stores
        if ($this->isMerchant($user) && $this->isMember($user, $order->store)) {
            return $this->decision($user, 'view', true, $order);
        }

        return $this->decision($user, 'view', false, $order);
    }

    public function update(User $user, Order $order): bool
    {
        // Rule: Only merchant admins can update orders
        return $this->decision(
            $user, 
            'update', 
            $this->isMerchant($user) && $this->isAdmin($user, $order->store), 
            $order
        );
    }

    public function cancel(User $user, Order $order): bool
    {
        // Rule 1: Owners can cancel their own orders
        if ($user->id === $order->user_id) {
            return $this->decision($user, 'cancel', true, $order);
        }

        // Rule 2: Merchants can cancel orders for their stores
        if ($this->isMerchant($user) && $this->isMember($user, $order->store)) {
            return $this->decision($user, 'cancel', true, $order);
        }

        return $this->decision($user, 'cancel', false, $order);
    }
}
