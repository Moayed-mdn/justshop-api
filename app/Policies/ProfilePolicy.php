<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Exceptions\Authorization\PermissionDeniedException;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

class ProfilePolicy
{
    use InteractsWithPolicyTelemetry;

    public function view(User $user, User $profileUser): bool
    {
        if ($user->id === $profileUser->id) {
            if ($user->can(PermissionEnum::PROFILE_VIEW)) {
                return $this->decision($user, 'view', true, $profileUser);
            }

            $this->denyWithContext('profile', 'view', PermissionEnum::PROFILE_VIEW);
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value) && $user->can(PermissionEnum::PROFILE_VIEW)) {
            return $this->decision($user, 'view', true, $profileUser);
        }

        $this->denyWithContext('profile', 'view', PermissionEnum::PROFILE_VIEW);
    }

    public function updateInfo(User $user, User $profileUser): bool
    {
        if ($user->id === $profileUser->id) {
            if ($user->can(PermissionEnum::PROFILE_UPDATE_INFO)) {
                return $this->decision($user, 'updateInfo', true, $profileUser);
            }

            $this->denyWithContext('profile', 'update_info', PermissionEnum::PROFILE_UPDATE_INFO);
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value) && $user->can(PermissionEnum::PROFILE_UPDATE_INFO)) {
            return $this->decision($user, 'updateInfo', true, $profileUser);
        }

        $this->denyWithContext('profile', 'update_info', PermissionEnum::PROFILE_UPDATE_INFO);
    }

    public function updatePassword(User $user, User $profileUser): bool
    {
        if ($user->id === $profileUser->id) {
            if ($user->can(PermissionEnum::PROFILE_UPDATE_PASSWORD)) {
                return $this->decision($user, 'updatePassword', true, $profileUser);
            }

            $this->denyWithContext('profile', 'update_password', PermissionEnum::PROFILE_UPDATE_PASSWORD);
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value) && $user->can(PermissionEnum::PROFILE_UPDATE_PASSWORD)) {
            return $this->decision($user, 'updatePassword', true, $profileUser);
        }

        $this->denyWithContext('profile', 'update_password', PermissionEnum::PROFILE_UPDATE_PASSWORD);
    }

    public function updateAvatar(User $user, User $profileUser): bool
    {
        if ($user->id === $profileUser->id) {
            if ($user->can(PermissionEnum::PROFILE_UPDATE_AVATAR)) {
                return $this->decision($user, 'updateAvatar', true, $profileUser);
            }

            $this->denyWithContext('profile', 'update_avatar', PermissionEnum::PROFILE_UPDATE_AVATAR);
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value) && $user->can(PermissionEnum::PROFILE_UPDATE_AVATAR)) {
            return $this->decision($user, 'updateAvatar', true, $profileUser);
        }

        $this->denyWithContext('profile', 'update_avatar', PermissionEnum::PROFILE_UPDATE_AVATAR);
    }

    public function delete(User $user, User $profileUser): bool
    {
        if ($user->id === $profileUser->id) {
            if ($user->can(PermissionEnum::PROFILE_DELETE)) {
                return $this->decision($user, 'delete', true, $profileUser);
            }

            $this->denyWithContext('profile', 'delete', PermissionEnum::PROFILE_DELETE);
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value) && $user->can(PermissionEnum::PROFILE_DELETE)) {
            return $this->decision($user, 'delete', true, $profileUser);
        }

        $this->denyWithContext('profile', 'delete', PermissionEnum::PROFILE_DELETE);
    }

    private function denyWithContext(string $resource, string $action, string $permission): never
    {
        throw new PermissionDeniedException($resource, $action, $permission);
    }
}
