<?php

namespace App\Observers;

use App\Models\Store;
use App\Models\BillingAccount;
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
                'stores_count' => DB::raw("GREATEST(stores_count + ({$delta}), 0)")
            ]);
        
        if ($updated === 0) {
            Log::warning('StoreObserver: No billing account found for owner', [
                'owner_id' => $ownerId,
                'delta' => $delta,
            ]);
        }
    }
}
