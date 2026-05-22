<?php
// app/Policies/PaymentMethodPolicy.php
namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

class PaymentMethodPolicy
{
    use InteractsWithPolicyTelemetry;

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->decision($user, 'update', $user->id === $paymentMethod->user_id, $paymentMethod);
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->decision($user, 'delete', $user->id === $paymentMethod->user_id, $paymentMethod);
    }
}
