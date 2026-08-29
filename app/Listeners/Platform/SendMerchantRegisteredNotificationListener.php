<?php

declare(strict_types=1);

namespace App\Listeners\Platform;

use App\Domain\Shared\Events\MerchantRegistered;
use App\Listeners\Concerns\EnsuresSingleNotificationDispatch;
use App\Notifications\Platform\MerchantRegisteredNotification;
use App\Repositories\Notification\PlatformRecipientRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendMerchantRegisteredNotificationListener implements ShouldQueue
{
    use EnsuresSingleNotificationDispatch;

    public function __construct(
        private readonly PlatformRecipientRepository $platformRecipients,
    ) {
    }

    public function handle(MerchantRegistered $event): void
    {
        if (!$this->claimOnce("merchant-registered:{$event->userId}")) {
            return;
        }

        $admins = $this->platformRecipients->listAdminRecipients();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new MerchantRegisteredNotification($event->userId, $event->name));
    }
}