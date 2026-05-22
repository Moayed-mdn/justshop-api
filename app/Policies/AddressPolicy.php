<?php
// app/Policies/AddressPolicy.php
namespace App\Policies;

use App\Models\Address;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

class AddressPolicy
{
    use InteractsWithPolicyTelemetry;

    public function view(User $user, Address $address): bool
    {
        return $this->decision($user, 'view', $user->id === $address->user_id, $address);
    }

    public function update(User $user, Address $address): bool
    {
        return $this->decision($user, 'update', $user->id === $address->user_id, $address);
    }

    public function delete(User $user, Address $address): bool
    {
        return $this->decision($user, 'delete', $user->id === $address->user_id, $address);
    }
}
