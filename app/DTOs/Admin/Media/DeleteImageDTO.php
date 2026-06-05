<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Media;

use App\Enums\MediaContextEnum;
use App\Http\Requests\Admin\Media\DeleteImageRequest;

class DeleteImageDTO
{
    public function __construct(
        public int $storeId,
        public MediaContextEnum $context,
        public string $path,
    ) {}

    public static function fromRequest(
        DeleteImageRequest $request,
        int $storeId,
    ): self {
        return new self(
            storeId: $storeId,
            context: MediaContextEnum::from($request->input('context')),
            path: $request->input('path'),
        );
    }
}
