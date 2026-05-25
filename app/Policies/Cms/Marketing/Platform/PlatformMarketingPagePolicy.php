<?php

declare(strict_types=1);

namespace App\Policies\Cms\Marketing\Platform;

use App\Enums\PermissionEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlatformMarketingPagePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::MARKETING_PLATFORM_VIEW);
    }

    public function view(User $user): bool
    {
        return $user->can(PermissionEnum::MARKETING_PLATFORM_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::MARKETING_PLATFORM_CREATE);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionEnum::MARKETING_PLATFORM_UPDATE);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::MARKETING_PLATFORM_DELETE);
    }

    public function publish(User $user): bool
    {
        return $user->can(PermissionEnum::MARKETING_PLATFORM_PUBLISH);
    }
}
