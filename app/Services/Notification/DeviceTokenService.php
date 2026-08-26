<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\Notification\DevicePlatformEnum;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Device token registration is deliberately a single small service rather
 * than a Repository+Service pair — it's CRUD on one simple table with one
 * quirk (upsert-by-token), which doesn't warrant the extra layer used
 * elsewhere for genuinely complex domains (Order, Lead).
 */
class DeviceTokenService
{
    /**
     * Register (or re-claim) a device token for a user.
     *
     * Tokens are unique platform-wide, not per-user: if the same token is
     * already registered (e.g. a shared/kiosk device where a different
     * user previously logged in, or the same user simply re-registering
     * on app relaunch), the existing row is reassigned/refreshed rather
     * than raising a duplicate-key error.
     */
    public function registerForUser(
        User $user,
        string $token,
        DevicePlatformEnum $platform,
        ?string $deviceId,
        ?string $deviceName,
    ): DeviceToken {
        return DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ],
        );
    }

    /**
     * Remove a token, scoped to the given user — a user can never remove
     * (or even discover the existence of) another user's device token.
     */
    public function removeForUser(User $user, string $token): bool
    {
        return $user->deviceTokens()
            ->where('token', $token)
            ->delete() > 0;
    }

    /**
     * @return Collection<int, DeviceToken>
     */
    public function listForUser(User $user): Collection
    {
        return $user->deviceTokens()->latest('last_used_at')->get();
    }
}
