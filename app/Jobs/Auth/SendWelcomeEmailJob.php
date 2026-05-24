<?php

declare(strict_types=1);

namespace App\Jobs\Auth;

use App\Models\User;
use App\Notifications\Auth\WelcomeMerchantNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendWelcomeEmailJob
 *
 * Sends a welcome email to a newly registered merchant.
 * Triggered by the MerchantRegistered domain event listener.
 *
 * Design rules:
 * - Idempotent: sending a welcome email twice is acceptable (rare edge case).
 * - Retryable: up to 3 attempts with exponential backoff.
 * - Queue: 'notifications' (shared notification queue).
 * - Timeout: 30 seconds.
 */
class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public int $backoff = 5;

    public function __construct(
        private readonly int $userId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning('SendWelcomeEmailJob: user not found, skipping.', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        $user->notify(new WelcomeMerchantNotification());
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendWelcomeEmailJob: failed after all retries.', [
            'user_id'   => $this->userId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
