<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Thin wrapper over Laravel's native Notifiable relations
 * ($user->notifications, $user->unreadNotifications) — deliberately not a
 * bespoke notification-center data model, so every Notification class's
 * toDatabase() payload is automatically visible here with zero extra
 * plumbing.
 */
class NotificationCenterService
{
    public function paginate(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Marks a single notification as read, scoped to the given user so
     * nobody can mark (or even address) another user's notification.
     *
     * @return bool false if no matching unread notification exists for this user.
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }
}
