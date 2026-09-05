<?php

namespace App\Actions\Asset;

use App\Models\Asset\StoreAsset;
use App\Repositories\Asset\StoreAssetRepository;
use Illuminate\Support\Facades\Storage;

class DeleteAssetAction
{
    public function __construct(
        private StoreAssetRepository $assetRepository
    ) {
    }

    public function execute(StoreAsset $asset): bool
    {
        // Delete file from storage
        if (Storage::exists($asset->file_path)) {
            Storage::delete($asset->file_path);
        }

        return $this->assetRepository->delete($asset);
    }
}
