<?php

declare(strict_types=1);

namespace App\DTOs\Cms\MarketingPage\Admin;

use App\Http\Requests\Cms\MarketingPage\Admin\PublishMarketingPageRequest;

class PublishMarketingPageDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $publishedAt,
        public readonly int $updatedBy,
    ) {}

    public static function fromRequest(
        PublishMarketingPageRequest $request,
        int $id,
    ): self {
        return new self(
            id: $id,
            publishedAt: $request->input('published_at'),
            updatedBy: (int) $request->user()->id,
        );
    }
}
