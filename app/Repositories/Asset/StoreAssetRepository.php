<?php

namespace App\Repositories\Asset;

use App\Enums\Theme\AssetTypeEnum;
use App\Models\Asset\StoreAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class StoreAssetRepository
{
    /**
     * Find asset by ID
     */
    public function find(int $id): ?StoreAsset
    {
        return StoreAsset::find($id);
    }

    /**
     * Get all assets for a store
     */
    public function getAllForStore(int $storeId, ?AssetTypeEnum $type = null): Collection
    {
        $query = StoreAsset::where('store_id', $storeId)
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    /**
     * Get paginated assets for a store
     */
    public function getPaginatedForStore(int $storeId, int $perPage = 20, ?AssetTypeEnum $type = null): LengthAwarePaginator
    {
        $query = StoreAsset::where('store_id', $storeId)
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new asset
     */
    public function create(array $data): StoreAsset
    {
        return StoreAsset::create($data);
    }

    /**
     * Update asset
     */
    public function update(StoreAsset $asset, array $data): StoreAsset
    {
        $asset->update($data);
        return $asset->fresh();
    }

    /**
     * Delete asset
     */
    public function delete(StoreAsset $asset): bool
    {
        return $asset->delete();
    }

    /**
     * Get assets by type for a store
     */
    public function getByType(int $storeId, AssetTypeEnum $type): Collection
    {
        return StoreAsset::where('store_id', $storeId)
            ->ofType($type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get latest logo for a store
     */
    public function getLatestLogo(int $storeId): ?StoreAsset
    {
        return StoreAsset::where('store_id', $storeId)
            ->ofType(AssetTypeEnum::LOGO)
            ->latest()
            ->first();
    }

    /**
     * Get latest favicon for a store
     */
    public function getLatestFavicon(int $storeId): ?StoreAsset
    {
        return StoreAsset::where('store_id', $storeId)
            ->ofType(AssetTypeEnum::FAVICON)
            ->latest()
            ->first();
    }
}
