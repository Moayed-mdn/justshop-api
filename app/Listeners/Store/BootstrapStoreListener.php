<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Domain\Shared\Events\StoreCreated;
use App\Jobs\Store\BootstrapStoreJob;
use Illuminate\Support\Facades\Log;

/**
 * BootstrapStoreListener
 *
 * Listens for StoreCreated domain event.
 * Dispatches BootstrapStoreJob asynchronously.
 *
 * Architecture rule: listeners MUST NOT contain business logic.
 * They are dispatch bridges only: Event → Job → Service.
 */
class BootstrapStoreListener
{
    public function handle(StoreCreated $event): void
    {
        Log::info('Listener: BootstrapStoreListener handling StoreCreated event', [
            'store_id' => $event->storeId,
        ]);

        BootstrapStoreJob::dispatch($event->storeId);
    }
}
