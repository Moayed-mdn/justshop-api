<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\BlogPost;
use App\Models\User;

class BlogPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function view(User $user, BlogPost $post): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function update(User $user, BlogPost $post): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function delete(User $user, BlogPost $post): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function publish(User $user, BlogPost $post): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function unpublish(User $user, BlogPost $post): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function schedule(User $user, BlogPost $post): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }
}
