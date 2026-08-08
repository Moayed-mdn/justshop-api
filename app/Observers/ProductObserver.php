<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\StoreEntitlementSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProductObserver
 * 
 * Maintains atomic count of products in store_entitlement_snapshots.
 * Uses DB::afterCommit to ensure counts update only after transaction success.
 * Updates are atomic (DB::raw with GREATEST) to prevent race conditions.
 */
class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        DB::afterCommit(fn() => $this->adjustCount($product->store_id, +1));
    }

    /**
     * Handle the Product "deleted" event (soft delete).
     */
    public function deleted(Product $product): void
    {
        DB::afterCommit(fn() => $this->adjustCount($product->store_id, -1));
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        DB::afterCommit(fn() => $this->adjustCount($product->store_id, +1));
    }

    /**
     * Handle the Product "force deleted" event (permanent delete).
     */
    public function forceDeleted(Product $product): void
    {
        DB::afterCommit(fn() => $this->adjustCount($product->store_id, -1));
    }

    /**
     * Atomically adjust the products_count for a store.
     * 
     * Uses GREATEST to prevent negative counts.
     * Runs synchronously (not queued) because it's a simple, fast UPDATE.
     */
    private function adjustCount(int $storeId, int $delta): void
    {
        $updated = StoreEntitlementSnapshot::where('store_id', $storeId)
            ->update([
                'products_count' => DB::raw("GREATEST(products_count + ({$delta}), 0)")
            ]);

        if ($updated === 0) {
            Log::warning('ProductObserver: No snapshot found to update', [
                'store_id' => $storeId,
                'delta' => $delta,
            ]);
        }
    }
}
