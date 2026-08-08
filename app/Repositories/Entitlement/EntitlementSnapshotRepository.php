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
     * 
     * Uses true atomic upsert to prevent race conditions between
     * sync (UpgradePlanAction) and async (webhook listener) updates.
     */
    public function upsert(int $storeId, array $data): StoreEntitlementSnapshot
    {
        $data['store_id'] = $storeId;
        $data['refreshed_at'] = now();
        $data['updated_at'] = now();
        
        // Cast datetime fields
        foreach (['expires_at', 'refreshed_at', 'created_at', 'updated_at'] as $field) {
            if (isset($data[$field]) && $data[$field] instanceof \DateTimeInterface) {
                $data[$field] = $data[$field]->format('Y-m-d H:i:s');
            }
        }
        
        // Cast JSON fields
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }
        
        // Use atomic upsert (Laravel 8+)
        // ON DUPLICATE KEY UPDATE (MySQL) or ON CONFLICT DO UPDATE (PostgreSQL)
        \DB::table('store_entitlement_snapshots')->upsert(
            $data,
            ['store_id'], // unique constraint
            array_keys(array_diff_key($data, ['store_id' => null, 'created_at' => null])) // columns to update
        );
        
        return StoreEntitlementSnapshot::where('store_id', $storeId)->first();
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
