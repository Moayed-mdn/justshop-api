<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Cms\MarketingPage;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * Marketing Page Policy
 *
 * Marketing pages are platform-level content.
 * Only super_admin users can manage them.
 */
class MarketingPagePolicy
{
    use InteractsWithPolicyTelemetry;

    public function viewAny(User $user): bool
    {
        return $this->decision(
            $user,
            'viewAny',
            $user->can(PermissionEnum::CMS_PAGE_VIEW)
        );
    }

    public function view(User $user, MarketingPage $page): bool
    {
        return $this->decision(
            $user,
            'view',
            $user->can(PermissionEnum::CMS_PAGE_VIEW),
            $page
        );
    }

    public function create(User $user): bool
    {
        return $this->decision(
            $user,
            'create',
            $user->can(PermissionEnum::CMS_PAGE_CREATE)
        );
    }

    public function update(User $user, MarketingPage $page): bool
    {
        return $this->decision(
            $user,
            'update',
            $user->can(PermissionEnum::CMS_PAGE_UPDATE),
            $page
        );
    }

    public function delete(User $user, MarketingPage $page): bool
    {
        return $this->decision(
            $user,
            'delete',
            $user->can(PermissionEnum::CMS_PAGE_DELETE),
            $page
        );
    }

    public function publish(User $user, MarketingPage $page): bool
    {
        return $this->decision(
            $user,
            'publish',
            $user->can(PermissionEnum::CMS_PAGE_PUBLISH),
            $page
        );
    }
}
