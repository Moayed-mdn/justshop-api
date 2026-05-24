<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Cms\CmsDocument;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * CMS Document Policy
 *
 * Documentation is platform-level content.
 * Uses permission-based authorization for granular control.
 */
class CmsDocumentPolicy
{
    use InteractsWithPolicyTelemetry;

    public function viewAny(User $user): bool
    {
        return $this->decision(
            $user,
            'viewAny',
            $user->can(PermissionEnum::CMS_DOC_VIEW)
        );
    }

    public function view(User $user, CmsDocument $document): bool
    {
        return $this->decision(
            $user,
            'view',
            $user->can(PermissionEnum::CMS_DOC_VIEW),
            $document
        );
    }

    public function create(User $user): bool
    {
        return $this->decision(
            $user,
            'create',
            $user->can(PermissionEnum::CMS_DOC_CREATE)
        );
    }

    public function update(User $user, CmsDocument $document): bool
    {
        return $this->decision(
            $user,
            'update',
            $user->can(PermissionEnum::CMS_DOC_UPDATE),
            $document
        );
    }

    public function delete(User $user, CmsDocument $document): bool
    {
        return $this->decision(
            $user,
            'delete',
            $user->can(PermissionEnum::CMS_DOC_DELETE),
            $document
        );
    }

    public function publish(User $user, CmsDocument $document): bool
    {
        return $this->decision(
            $user,
            'publish',
            $user->can(PermissionEnum::CMS_DOC_PUBLISH),
            $document
        );
    }

    public function reorder(User $user): bool
    {
        return $this->decision(
            $user,
            'reorder',
            $user->can(PermissionEnum::CMS_DOC_UPDATE)
        );
    }
}
