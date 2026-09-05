<?php

namespace App\Observers;

use App\Models\Store;
use App\Models\BillingAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StoreObserver
 * 
 * Maintains atomic count of stores in billing_accounts table.
 * 
 * Key difference from ProductObserver:
 * - stores_count is account-level, not store-level
 * - Updated in billing_accounts table (one row per owner)
 * - No duplication/fan-out needed
 * 
 * Why billing_accounts not store_entitlement_snapshots?
 * - stores.count is a property of the billing account/owner, not individual stores
 * - Storing it per-snapshot creates duplication and divergence risk
 * - Single source of truth is cleaner and safer
 */
class StoreObserver
{
    /**
     * Handle the Store "updated" event.
     *
     * RuntimeStoreResolver caches the domain -> store lookup (it's queried
     * on every single storefront runtime request). Bust that cache whenever
     * a store's domain actually changes so a re-pointed domain doesn't keep
     * resolving to stale data until the TTL expires.
     */
    public function updated(Store $store): void
    {
        if ($store->wasChanged('domain')) {
            $previousDomain = $store->getOriginal('domain');
            if (is_string($previousDomain) && $previousDomain !== '') {
                Cache::forget('storefront_runtime:tenant_domain:' . strtolower(trim($previousDomain)));
            }
            if (is_string($store->domain) && $store->domain !== '') {
                Cache::forget('storefront_runtime:tenant_domain:' . strtolower(trim($store->domain)));
            }
        }
    }

    /**
     * Handle the Store "created" event.
     */
    public function created(Store $store): void
    {
        DB::afterCommit(fn() => $this->adjustCount($store->owner_id, +1));
    }

    /**
     * Handle the Store "deleted" event (soft delete).
     */
    public function deleted(Store $store): void
    {
        DB::afterCommit(fn() => $this->adjustCount($store->owner_id, -1));
    }

    /**
     * Handle the Store "restored" event.
     */
    public function restored(Store $store): void
    {
        DB::afterCommit(fn() => $this->adjustCount($store->owner_id, +1));
    }

    /**
     * Handle the Store "force deleted" event.
     */
    public function forceDeleted(Store $store): void
    {
        DB::afterCommit(fn() => $this->adjustCount($store->owner_id, -1));
    }

    /**
     * Atomically adjust stores_count in billing_accounts.
     * 
     * Updates a single row (the billing account for this owner).
     * No fan-out, no duplication, no drift risk.
     */
    private function adjustCount(int $ownerId, int $delta): void
    {
        $updated = BillingAccount::where('owner_user_id', $ownerId)
            ->update([
                // Portable clamp-at-zero: GREATEST() is MySQL/Postgres-only and
                // is not available on SQLite (used in the test suite), so a CASE
                // expression is used instead since it works identically on all
                // three drivers.
                'stores_count' => DB::raw("CASE WHEN (stores_count + ({$delta})) > 0 THEN (stores_count + ({$delta})) ELSE 0 END")
            ]);
        
        if ($updated === 0) {
            Log::warning('StoreObserver: No billing account found for owner', [
                'owner_id' => $ownerId,
                'delta' => $delta,
            ]);
        }
    }
}
