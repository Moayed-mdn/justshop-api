<?php
// app/Policies/OrderPolicy.php
namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    use HasStoreMembership;

    public function view(User $user, Order $order)
    {
        // Owner of the order can view it
        if ($user->id === $order->user_id) {
            return true;
        }

        // Store staff can view orders in their store
        return $this->isMember($user, $order->store);
    }

    public function update(User $user, Order $order)
    {
        // Customers cannot update their own orders (except maybe cancellation, but that's usually a separate action)
        // Store admins can update orders
        return $this->isAdmin($user, $order->store);
    }

    public function cancel(User $user, Order $order)
    {
        // Owner can cancel if it's still pending
        if ($user->id === $order->user_id) {
            return true; // Business logic for cancellation state should be in the Action
        }

        // Store staff can cancel
        return $this->isMember($user, $order->store);
    }
}