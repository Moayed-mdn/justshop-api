<?php

namespace App\Repositories\Entitlement;

use App\Models\StoreEntitlementSnapshot;
use Illuminate\Support\Collection;

class EntitlementSnapshotRepository
{
    /**
     * Find snapshot by store ID.
     */
    public function findByStoreId(int $storeId): ?StoreEntitlementSnapshot
    {
        return StoreEntitlementSnapshot::where('store_id', $storeId)->first();
    }

    /**
     * Find snapshot by store ID or fail.
     */
    public function findByStoreIdOrFail(int $storeId): StoreEntitlementSnapshot
    {
        return StoreEntitlementSnapshot::where('store_id', $storeId)->firstOrFail();
    }

    /**
     * Create or update snapshot for a store.
     */
    public function upsert(int $storeId, array $data): StoreEntitlementSnapshot
    {
        return StoreEntitlementSnapshot::updateOrCreate(
            ['store_id' => $storeId],
            array_merge($data, ['refreshed_at' => now()])
        );
    }

    /**
     * Get all snapshots for a billing account.
     */
    public function getAllForAccount(int $billingAccountId): Collection
    {
        return StoreEntitlementSnapshot::where('billing_account_id', $billingAccountId)->get();
    }

    /**
     * Get stale snapshots that need refresh.
     */
    public function getStale(int $ttlMinutes = 60): Collection
    {
        return StoreEntitlementSnapshot::stale($ttlMinutes)->get();
    }

    /**
     * Delete snapshot for a store.
     */
    public function delete(int $storeId): bool
    {
        return StoreEntitlementSnapshot::where('store_id', $storeId)->delete();
    }

    /**
     * Refresh snapshot timestamp.
     */
    public function touch(int $storeId): void
    {
        StoreEntitlementSnapshot::where('store_id', $storeId)
            ->update(['refreshed_at' => now()]);
    }
}
