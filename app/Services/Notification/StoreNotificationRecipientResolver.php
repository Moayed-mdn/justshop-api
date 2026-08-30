<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\Enterprise\MembershipLifecycleEnum;
use App\Enums\Notification\NotificationCategoryEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\PermissionResolver;
use Illuminate\Support\Collection;

/**
 * Implements the product's recipient-targeting rule for store-scoped
 * notifications (see docs/notifications/ARCHITECTURE.md §2):
 *
 * - Store Admin receives every category.
 * - Store Staff only receives a category if they currently hold the
 *   permission associated with it for that store — reusing the same
 *   PermissionResolver the rest of the app uses for authorization, so
 *   this stays correct automatically as the permission model evolves.
 * - ADMIN_ONLY categories (Stripe Connect status, platform subscription
 *   billing) never reach staff, regardless of permissions.
 *
 * Only ACTIVE memberships are considered (invited/suspended/revoked
 * members don't receive notifications).
 */
class StoreNotificationRecipientResolver
{
    public function __construct(
        private readonly PermissionResolver $permissionResolver,
    ) {
    }

    /**
     * @return Collection<int, User>
     */
    public function resolve(Store $store, NotificationCategoryEnum $category): Collection
    {
        $members = $store->users()
            ->wherePivot('lifecycle_status', MembershipLifecycleEnum::ACTIVE->value)
            ->get();

        $recipients = new Collection();

        foreach ($members as $member) {
            if ($member->pivot->role === StoreRoleEnum::STORE_ADMIN->value) {
                $recipients->push($member);

                continue;
            }

            if ($this->staffQualifies($member, $store, $category)) {
                $recipients->push($member);
            }
        }

        return $recipients->unique('id')->values();
    }

    /**
     * Convenience for categories that should only ever reach Store Admin
     * (e.g. Stripe Connect status, platform billing) — resolves the same
     * way but staff are never considered.
     *
     * @return Collection<int, User>
     */
    public function resolveAdminsOnly(Store $store): Collection
    {
        return $this->resolve($store, NotificationCategoryEnum::ADMIN_ONLY);
    }

    private function staffQualifies(User $staffMember, Store $store, NotificationCategoryEnum $category): bool
    {
        $permission = $category->staffGatePermission();

        if ($permission === null) {
            // ADMIN_ONLY — staff never qualify.
            return false;
        }

        $permissions = $this->permissionResolver->resolve($staffMember, $store);

        return in_array($permission, $permissions, true);
    }
}
