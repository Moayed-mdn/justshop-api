<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\BlogPost;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * Blog Post Policy
 *
 * Blog posts are platform-level content.
 * Uses permission-based authorization for granular control.
 */
class BlogPostPolicy
{
    use InteractsWithPolicyTelemetry;

    public function viewAny(User $user): bool
    {
        return $this->decision(
            $user,
            'viewAny',
            $user->can(PermissionEnum::CMS_BLOG_VIEW)
        );
    }

    public function view(User $user, BlogPost $post): bool
    {
        return $this->decision(
            $user,
            'view',
            $user->can(PermissionEnum::CMS_BLOG_VIEW),
            $post
        );
    }

    public function create(User $user): bool
    {
        return $this->decision(
            $user,
            'create',
            $user->can(PermissionEnum::CMS_BLOG_CREATE)
        );
    }

    public function update(User $user, BlogPost $post): bool
    {
        return $this->decision(
            $user,
            'update',
            $user->can(PermissionEnum::CMS_BLOG_UPDATE),
            $post
        );
    }

    public function delete(User $user, BlogPost $post): bool
    {
        return $this->decision(
            $user,
            'delete',
            $user->can(PermissionEnum::CMS_BLOG_DELETE),
            $post
        );
    }

    public function publish(User $user, BlogPost $post): bool
    {
        return $this->decision(
            $user,
            'publish',
            $user->can(PermissionEnum::CMS_BLOG_PUBLISH),
            $post
        );
    }

    public function unpublish(User $user, BlogPost $post): bool
    {
        return $this->decision(
            $user,
            'unpublish',
            $user->can(PermissionEnum::CMS_BLOG_PUBLISH),
            $post
        );
    }

    public function schedule(User $user, BlogPost $post): bool
    {
        return $this->decision(
            $user,
            'schedule',
            $user->can(PermissionEnum::CMS_BLOG_PUBLISH),
            $post
        );
    }
}
