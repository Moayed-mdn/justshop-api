<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Media;

use App\Enums\MediaContextEnum;
use App\Http\Requests\Admin\Media\UploadImageRequest;
use Illuminate\Http\UploadedFile;

class UploadImageDTO
{
    public function __construct(
        public int $storeId,
        public MediaContextEnum $context,
        public UploadedFile $file,
    ) {}

    public static function fromRequest(
        UploadImageRequest $request,
        int $storeId,
    ): self {
        return new self(
            storeId: $storeId,
            context: MediaContextEnum::from($request->input('context')),
            file: $request->file('image'),
        );
    }
}
