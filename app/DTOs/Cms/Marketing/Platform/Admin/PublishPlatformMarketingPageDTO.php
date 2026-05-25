<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Marketing\Platform\Admin;

use App\Http\Requests\Cms\Marketing\Platform\Admin\PublishPlatformMarketingPageRequest;

class PublishPlatformMarketingPageDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $publishedAt,
        public readonly int $updatedBy,
    ) {}

    public static function fromRequest(PublishPlatformMarketingPageRequest $request, int $id): self
    {
        return new self(
            id: $id,
            publishedAt: $request->input('published_at'),
            updatedBy: (int) $request->user()->id,
        );
    }
}
