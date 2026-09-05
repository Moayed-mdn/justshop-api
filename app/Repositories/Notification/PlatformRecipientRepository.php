<?php

declare(strict_types=1);

namespace App\Repositories\Notification;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Resolves recipients for platform/admin-facing notifications.
 *
 * Mirrors LeadRepository::listAdminRecipients() (SUPER_ADMIN only, guarded
 * against the role not existing yet in a fresh install) — intentionally
 * not touching that repository since it's Lead-specific and unrelated
 * modules shouldn't be coupled together for a five-line query.
 *
 * SUPPORT_AGENT is deliberately excluded for now, matching that existing
 * convention; extend here if support agents should start receiving
 * operational alerts too.
 */
class PlatformRecipientRepository
{
    /**
     * Users who should receive platform-level notifications (new store
     * created, new lead, etc). Roles in this app are always seeded/checked
     * against the 'web' guard (see PermissionSeeder) -- querying without an
     * explicit guard falls back to whichever guard is active for the
     * CURRENT request (merchant/customer/platform), which does not have
     * this role and throws RoleDoesNotExist instead of returning no rows.
     *
     * @return Collection<int, User>
     */
    public function listAdminRecipients(): Collection
    {
        if (!Role::query()->where('name', RoleEnum::SUPER_ADMIN->value)->where('guard_name', 'web')->exists()) {
            return new Collection();
        }

        return User::query()
            ->role(RoleEnum::SUPER_ADMIN->value, 'web')
            ->get();
    }
}
