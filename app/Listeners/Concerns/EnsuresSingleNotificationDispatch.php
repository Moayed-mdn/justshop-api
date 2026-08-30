<?php

declare(strict_types=1);

namespace App\Listeners\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Guards a notification-sending listener against running more than once
 * for the same underlying business event.
 *
 * Why this exists: Notification::send()/->notify() are not idempotent —
 * calling them twice creates two separate notification rows. A listener
 * can end up running twice for reasons that have nothing to do with the
 * event being dispatched twice, e.g.:
 * - The `database` queue driver's `retry_after` firing before a slower
 *   listener finishes, causing a second worker to pick up and re-run the
 *   same job while the first is still executing (a well-known Laravel
 *   footgun — see config/queue.php's retry_after and consider raising it
 *   if this fires often).
 * - A worker restart/deploy mid-job.
 * - A race between two near-simultaneous requests both passing a
 *   "not yet processed" check before either commits (separately guarded
 *   at the source for orders — see CancelOrderAction/UpdateOrderStatusAction
 *   — but this is a second, independent layer of protection).
 *
 * Cache::add() is atomic (a single INSERT-if-not-exists at the cache
 * driver level), so under true concurrency only one execution ever wins
 * the guard, regardless of which of the above causes is at play.
 */
trait EnsuresSingleNotificationDispatch
{
    /**
     * @return bool true if this is the first time this key has been seen
     *              (caller should proceed); false if it's a duplicate
     *              (caller should return early without sending anything).
     */
    private function claimOnce(string $key, int $ttlHours = 24): bool
    {
        $claimed = Cache::add("notification-dispatch:{$key}", true, now()->addHours($ttlHours));

        if (!$claimed) {
            Log::channel('notifications')->info('Skipped duplicate notification dispatch', [
                'key' => $key,
                'listener' => static::class,
            ]);
        }

        return $claimed;
    }
}