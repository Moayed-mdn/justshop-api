<?php

declare(strict_types=1);

namespace App\Actions\Admin\Media;

use App\DTOs\Admin\Media\UploadImageDTO;
use App\Exceptions\Media\MediaUploadFailedException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadImageAction
{
    /**
     * Execute image upload.
     *
     * @return array{path: string, url: string, full_url: string}
     * @throws MediaUploadFailedException
     */
    public function execute(UploadImageDTO $dto): array
    {
        try {
            // Generate unique filename
            $extension = $dto->file->getClientOriginalExtension();
            $filename = Str::random(20) . '.' . $extension;

            // Get storage directory for context
            $directory = $dto->context->getStoragePath();

            // Store file in default disk
            $path = $dto->file->storeAs(
                $directory,
                $filename
            );

            if (!$path) {
                throw new MediaUploadFailedException(
                    __('media.upload_failed')
                );
            }

            // Generate URLs
            $url = Storage::url($path);
            $fullUrl = $url;

            return [
                'path' => $path,
                'url' => $url,
                'full_url' => $fullUrl,
            ];
        } catch (\Exception $e) {
            throw new MediaUploadFailedException(
                __('media.upload_failed') . ': ' . $e->getMessage()
            );
        }
    }
}
