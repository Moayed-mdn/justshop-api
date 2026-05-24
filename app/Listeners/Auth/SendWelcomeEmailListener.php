<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Domain\Shared\Events\MerchantRegistered;
use App\Jobs\Auth\SendWelcomeEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * SendWelcomeEmailListener
 *
 * Listens for MerchantRegistered domain event.
 * Dispatches SendWelcomeEmailJob asynchronously.
 *
 * Architecture rule: listeners MUST NOT contain business logic.
 * They are dispatch bridges only: Event → Job → Service.
 */
class SendWelcomeEmailListener implements ShouldQueue
{
    public function handle(MerchantRegistered $event): void
    {
        SendWelcomeEmailJob::dispatch($event->userId);
    }
}
