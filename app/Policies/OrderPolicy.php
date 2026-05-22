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
        if ($user->id === $order->user_id) {
            return $this->decision($user, 'view', true, $order);
        }

        return $this->decision($user, 'view', $this->isMember($user, $order->store), $order);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->decision($user, 'update', $this->isAdmin($user, $order->store), $order);
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->id === $order->user_id) {
            return $this->decision($user, 'cancel', true, $order);
        }

        return $this->decision($user, 'cancel', $this->isMember($user, $order->store), $order);
    }
}
