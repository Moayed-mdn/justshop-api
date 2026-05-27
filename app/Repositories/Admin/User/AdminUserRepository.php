<?php

namespace App\Repositories\Admin\User;

use App\Enums\User\UserStatusEnum;
use App\Exceptions\User\UserNotFoundException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserRepository
{
    /**
     * List users belonging to a specific store (via store_user pivot)
     */
    public function listForStore(
        int $storeId,
        ?string $search = null,
        ?string $status = null,
        ?string $role = null,
        int $perPage = 15,
        ?int $excludeUserId = null,
    ): LengthAwarePaginator
    {
        $query = User::query()
            ->with('roles')
            ->join('store_user', 'users.id', '=', 'store_user.user_id')
            ->where('store_user.store_id', $storeId)
            ->select('users.*', 'store_user.role as store_role');

        if ($excludeUserId !== null) {
            $query->where('users.id', '!=', $excludeUserId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === UserStatusEnum::ACTIVE->value) {
            $query->where('is_active', true);
        } elseif ($status === UserStatusEnum::INACTIVE->value) {
            $query->where('is_active', false);
        }

        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new user and attach to store
     */
    public function create(int $storeId, string $name, string $email, string $password, \App\Enums\Store\StoreRoleEnum $role): User
    {
        $user = User::create([
            'name'            => $name,
            'email'           => $email,
            'password'        => \Illuminate\Support\Facades\Hash::make($password),
            'onboarding_step' => \App\Enums\Auth\OnboardingStepEnum::COMPLETED,
        ]);

        $user->stores()->attach($storeId, ['role' => $role->value]);

        return $user;
    }

    /**
     * Find a user in a specific store or throw exception
     */
    public function findInStore(int $userId, int $storeId): User
    {
        $user = User::query()
            ->join('store_user', 'users.id', '=', 'store_user.user_id')
            ->where('store_user.store_id', $storeId)
            ->where('users.id', $userId)
            ->select('users.*', 'store_user.role as store_role')
            ->first();

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    /**
     * Block a user (set is_active = false)
     */
    public function block(User $user): User
    {
        $user->is_active = false;
        $user->save();

        return $user;
    }

    /**
     * Unblock a user (set is_active = true)
     */
    public function unblock(User $user): User
    {
        $user->is_active = true;
        $user->save();

        return $user;
    }

    /**
     * Soft delete a user
     */
    public function softDelete(User $user): void
    {
        $user->delete();
    }

    /**
     * Restore a trashed user scoped by store
     */
    public function restore(int $userId, int $storeId): User
    {
        $user = User::withTrashed()
            ->join('store_user', 'users.id', '=', 'store_user.user_id')
            ->where('store_user.store_id', $storeId)
            ->where('users.id', $userId)
            ->select('users.*', 'store_user.role as store_role')
            ->first();

        if (!$user) {
            throw new UserNotFoundException();
        }

        $user->restore();

        return $this->findInStore($userId, $storeId);
    }
}
