<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Asset;

use App\Actions\Asset\DeleteAssetAction;
use App\Actions\Asset\UploadAssetAction;
use App\Enums\Theme\AssetTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\Asset\UpdateAssetRequest;
use App\Http\Requests\Merchant\Asset\UploadAssetRequest;
use App\Http\Resources\Asset\StoreAssetResource;
use App\Models\Asset\StoreAsset;
use App\Models\Store;
use App\Repositories\Asset\StoreAssetRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreAssetController extends Controller
{
    public function __construct(
        private StoreAssetRepository $assetRepository,
        private UploadAssetAction $uploadAssetAction,
        private DeleteAssetAction $deleteAssetAction,
    ) {
    }

    /**
     * Get all assets for a store
     */
    public function index(Request $request, Store $store): JsonResponse
    {
        $type = $request->query('type');
        $typeEnum = $type ? AssetTypeEnum::from($type) : null;

        $assets = $this->assetRepository->getPaginatedForStore($store->id, 20, $typeEnum);

        return $this->paginated(
            $assets,
            StoreAssetResource::collection($assets)
        );
    }

    /**
     * Upload a new asset
     */
    public function store(UploadAssetRequest $request, Store $store): JsonResponse
    {
        $validated = $request->validated();
        $type = AssetTypeEnum::from($validated['type']);

        $asset = $this->uploadAssetAction->execute(
            storeId: $store->id,
            name: $validated['name'],
            type: $type,
            file: $request->file('file'),
            altText: $validated['alt_text'] ?? null,
            description: $validated['description'] ?? null
        );

        return $this->success(
            new StoreAssetResource($asset),
            __('theme.asset_uploaded'),
            201
        );
    }

    /**
     * Update asset metadata
     */
    public function update(UpdateAssetRequest $request, Store $store, StoreAsset $asset): JsonResponse
    {
        $asset = $this->assetRepository->update($asset, $request->validated());

        return $this->success(
            new StoreAssetResource($asset),
            __('theme.asset_updated')
        );
    }

    /**
     * Delete an asset
     */
    public function destroy(Store $store, StoreAsset $asset): JsonResponse
    {
        $this->deleteAssetAction->execute($asset);

        return $this->success(null, __('theme.asset_deleted'));
    }
}
