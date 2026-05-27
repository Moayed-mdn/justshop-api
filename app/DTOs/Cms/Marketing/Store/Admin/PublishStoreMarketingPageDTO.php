<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Marketing\Store\Admin;

use App\Http\Requests\Cms\Marketing\Store\Admin\PublishStoreMarketingPageRequest;

class PublishStoreMarketingPageDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $storeId,
        public readonly ?string $publishedAt,
        public readonly int $updatedBy,
    ) {}

    public static function fromRequest(PublishStoreMarketingPageRequest $request, int $storeId, int $id): self
    {
        return new self(
            id: $id,
            storeId: $storeId,
            publishedAt: $request->input('published_at'),
            updatedBy: (int) $request->user()->id,
        );
    }
}
