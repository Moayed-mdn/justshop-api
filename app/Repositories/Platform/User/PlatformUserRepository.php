<?php

declare(strict_types=1);

namespace App\Repositories\Platform\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * PlatformUserRepository
 * 
 * Platform-level user repository (NOT store-scoped).
 * Used by super_admin to view/manage all users across the platform.
 */
class PlatformUserRepository
{
    /**
     * List all platform users with filtering and pagination
     */
    public function list(
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 25,
    ): LengthAwarePaginator {
        $query = User::query()
            ->with(['roles', 'stores'])
            ->withCount('stores');

        // Apply search filter
        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($role && $role !== 'all') {
            $query->whereHas('roles', function (Builder $q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Apply status filter
        if ($status && $status !== 'all') {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'suspended') {
                $query->where('is_active', false);
            }
        }

        // Apply sorting
        $allowedSortFields = ['created_at', 'updated_at', 'name', 'email'];
        if (in_array($sortBy, $allowedSortFields, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Find user by ID with relationships
     */
    public function findWithRelations(int $userId): ?User
    {
        return User::query()
            ->with(['roles', 'stores'])
            ->withCount('stores')
            ->find($userId);
    }

    /**
     * Update user basic information
     */
    public function update(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user->fresh(['roles', 'stores']);
    }

    /**
     * Suspend user (set is_active = false)
     */
    public function suspend(User $user): User
    {
        $user->is_active = false;
        $user->save();

        return $user->fresh(['roles']);
    }

    /**
     * Activate user (set is_active = true)
     */
    public function activate(User $user): User
    {
        $user->is_active = true;
        $user->save();

        return $user->fresh(['roles']);
    }

    /**
     * Delete user (soft delete)
     */
    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * Sync user roles
     */
    public function syncRoles(User $user, array $roles): User
    {
        $user->syncRoles($roles);

        return $user->fresh(['roles']);
    }
}

