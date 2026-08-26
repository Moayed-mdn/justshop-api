<?php

declare(strict_types=1);

namespace App\Jobs\Notification;

use App\Models\DeviceToken;
use App\Services\Fcm\FcmClient;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendFcmNotificationJob
 *
 * Sends one push message to one device token. Dispatched once per
 * registered device token by FcmChannel, so a user with three devices
 * gets three independent jobs — an invalid/expired token on one device
 * never blocks delivery to their other devices, and a transient failure
 * on one job retries independently via the queue's own backoff.
 *
 * Design rules (matching SendWelcomeEmailJob):
 * - Retryable: configurable attempts with backoff (config('notifications.fcm')).
 * - Queue: config('notifications.queue') (default: 'notifications').
 * - On confirmed invalid/unregistered token, deletes the DeviceToken row.
 */
class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $backoff;
    public int $timeout = 15;

    public function __construct(
        private readonly int $deviceTokenId,
        private readonly FcmMessage $message,
    ) {
        $this->tries = (int) config('notifications.fcm.max_tries', 3);
        $this->backoff = (int) config('notifications.fcm.backoff_seconds', 30);
        $this->onConnection(config('notifications.queue_connection'));
        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function handle(FcmClient $fcmClient): void
    {
        $deviceToken = DeviceToken::find($this->deviceTokenId);

        if (!$deviceToken) {
            // Token was removed (e.g. user unregistered it) between the
            // notification being triggered and this job running. Nothing
            // to do.
            return;
        }

        $result = $fcmClient->send($this->message, $deviceToken->token);

        if ($result->successful) {
            $deviceToken->update(['last_used_at' => now()]);

            return;
        }

        if ($result->tokenInvalid) {
            Log::channel('notifications')->info('Removing invalid FCM device token', [
                'device_token_id' => $deviceToken->id,
                'user_id' => $deviceToken->user_id,
                'reason' => $result->error,
            ]);

            $deviceToken->delete();

            return;
        }

        // Transient failure — throwing lets the queue retry per
        // $tries/$backoff; failed() below only fires once attempts are
        // exhausted.
        Log::channel('notifications')->warning('FCM send failed, will retry if attempts remain', [
            'device_token_id' => $deviceToken->id,
            'error' => $result->error,
        ]);

        throw new \RuntimeException('FCM send failed: '.$result->error);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('notifications')->error('SendFcmNotificationJob: failed after all retries.', [
            'device_token_id' => $this->deviceTokenId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
