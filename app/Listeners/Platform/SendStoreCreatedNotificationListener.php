<?php

declare(strict_types=1);

namespace App\Listeners\Platform;

use App\Domain\Shared\Events\StoreCreated;
use App\Notifications\Platform\StoreCreatedNotification;
use App\Repositories\Notification\PlatformRecipientRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendStoreCreatedNotificationListener implements ShouldQueue
{
    public function __construct(
        private readonly PlatformRecipientRepository $platformRecipients,
    ) {
    }

    public function handle(StoreCreated $event): void
    {
        $admins = $this->platformRecipients->listAdminRecipients();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new StoreCreatedNotification($event->storeId, $event->name));
    }
}
