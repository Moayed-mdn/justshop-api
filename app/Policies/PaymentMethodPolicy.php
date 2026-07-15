<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Exceptions\Authorization\PermissionDeniedException;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

class PaymentMethodPolicy
{
    use InteractsWithPolicyTelemetry;

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        if (!$user->can(PermissionEnum::PAYMENT_METHOD_UPDATE)) {
            $this->denyWithContext('payment_method', 'update', PermissionEnum::PAYMENT_METHOD_UPDATE);
        }

        if ($user->id === $paymentMethod->user_id) {
            return $this->decision($user, 'update', true, $paymentMethod);
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $this->decision($user, 'update', true, $paymentMethod);
        }

        $this->denyWithContext('payment_method', 'update', PermissionEnum::PAYMENT_METHOD_UPDATE);
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        if (!$user->can(PermissionEnum::PAYMENT_METHOD_DELETE)) {
            $this->denyWithContext('payment_method', 'delete', PermissionEnum::PAYMENT_METHOD_DELETE);
        }

        if ($user->id === $paymentMethod->user_id) {
            return $this->decision($user, 'delete', true, $paymentMethod);
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $this->decision($user, 'delete', true, $paymentMethod);
        }

        $this->denyWithContext('payment_method', 'delete', PermissionEnum::PAYMENT_METHOD_DELETE);
    }

    private function denyWithContext(string $resource, string $action, string $permission): never
    {
        throw new PermissionDeniedException($resource, $action, $permission);
    }
}
