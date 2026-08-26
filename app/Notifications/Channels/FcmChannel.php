<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Jobs\Notification\SendFcmNotificationJob;
use App\Models\User;
use App\Services\Fcm\FcmMessage;
use Illuminate\Notifications\Notification;

/**
 * FcmChannel
 *
 * Registered as the 'fcm' notification channel in AppServiceProvider.
 * A notification class opts in by returning 'fcm' from via() and
 * implementing toFcm($notifiable): FcmMessage.
 *
 * Fans out to every device token the notifiable currently has registered,
 * one queued job per token (see SendFcmNotificationJob) — so one dead
 * token never blocks delivery to the user's other devices, and a user
 * with zero registered devices is simply a no-op rather than an error.
 */
class FcmChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        if (!$notifiable instanceof User) {
            return;
        }

        /** @var FcmMessage $message */
        $message = $notification->toFcm($notifiable);

        $deviceTokens = $notifiable->deviceTokens()->get(['id']);

        foreach ($deviceTokens as $deviceToken) {
            SendFcmNotificationJob::dispatch($deviceToken->id, $message);
        }
    }
}
