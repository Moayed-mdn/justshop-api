<?php

namespace App\Actions\Asset;

use App\Enums\Theme\AssetTypeEnum;
use App\Exceptions\Asset\InvalidAssetTypeException;
use App\Models\Asset\StoreAsset;
use App\Repositories\Asset\StoreAssetRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadAssetAction
{
    public function __construct(
        private StoreAssetRepository $assetRepository
    ) {
    }

    public function execute(
        int $storeId,
        string $name,
        AssetTypeEnum $type,
        UploadedFile $file,
        ?string $altText = null,
        ?string $description = null
    ): StoreAsset {
        // Validate MIME type
        $allowedMimes = $type->allowedMimeTypes();
        if (!empty($allowedMimes) && !in_array($file->getMimeType(), $allowedMimes)) {
            throw new InvalidAssetTypeException(__('theme.invalid_file_type'));
        }

        // Store file
        $path = $file->store("stores/{$storeId}/assets/{$type->value}");
        $url = Storage::url($path);

        // Get image dimensions if it's an image
        $width = null;
        $height = null;
        if (str_starts_with($file->getMimeType(), 'image/')) {
            [$width, $height] = getimagesize($file->getRealPath());
        }

        return $this->assetRepository->create([
            'store_id' => $storeId,
            'name' => $name,
            'type' => $type,
            'file_path' => $path,
            'file_url' => $url,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt_text' => $altText,
            'description' => $description,
        ]);
    }
}
