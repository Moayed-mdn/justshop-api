<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\BlogPost;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

class BlogPostPolicy
{
    use InteractsWithPolicyTelemetry;

    public function viewAny(User $user): bool
    {
        return $this->decision($user, 'viewAny', $user->hasRole(RoleEnum::SUPER_ADMIN->value));
    }

    public function view(User $user, BlogPost $post): bool
    {
        return $this->decision($user, 'view', $user->hasRole(RoleEnum::SUPER_ADMIN->value), $post);
    }

    public function create(User $user): bool
    {
        return $this->decision($user, 'create', $user->hasRole(RoleEnum::SUPER_ADMIN->value));
    }

    public function update(User $user, BlogPost $post): bool
    {
        return $this->decision($user, 'update', $user->hasRole(RoleEnum::SUPER_ADMIN->value), $post);
    }

    public function delete(User $user, BlogPost $post): bool
    {
        return $this->decision($user, 'delete', $user->hasRole(RoleEnum::SUPER_ADMIN->value), $post);
    }

    public function publish(User $user, BlogPost $post): bool
    {
        return $this->decision($user, 'publish', $user->hasRole(RoleEnum::SUPER_ADMIN->value), $post);
    }

    public function unpublish(User $user, BlogPost $post): bool
    {
        return $this->decision($user, 'unpublish', $user->hasRole(RoleEnum::SUPER_ADMIN->value), $post);
    }

    public function schedule(User $user, BlogPost $post): bool
    {
        return $this->decision($user, 'schedule', $user->hasRole(RoleEnum::SUPER_ADMIN->value), $post);
    }
}
