<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

class DashboardPolicy
{
    use InteractsWithPolicyTelemetry;

    public function before(User $user, string $ability, mixed $store = null): ?bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $this->decision($user, $ability, true, $store, [
                'authorization_domain' => 'dashboard',
                'fallback_path_used' => false,
            ]);
        }

        return null;
    }

    public function viewStats(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewStats', $this->canView($user, $store), $store, [
            'authorization_domain' => 'dashboard',
            'fallback_path_used' => false,
        ]);
    }

    public function viewRecentOrders(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewRecentOrders', $this->canView($user, $store), $store, [
            'authorization_domain' => 'dashboard',
            'fallback_path_used' => false,
        ]);
    }

    public function viewTopProducts(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewTopProducts', $this->canView($user, $store), $store, [
            'authorization_domain' => 'dashboard',
            'fallback_path_used' => false,
        ]);
    }

    private function canView(User $user, Store $store): bool
    {
        return $user->stores()->where('store_id', $store->id)->exists()
            && $user->can(PermissionEnum::DASHBOARD_VIEW);
    }
}
