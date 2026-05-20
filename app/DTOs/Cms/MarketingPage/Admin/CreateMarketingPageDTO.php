<?php

declare(strict_types=1);

namespace App\DTOs\Cms\MarketingPage\Admin;

use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Enums\Cms\MarketingPage\MarketingPageTypeEnum;
use App\Http\Requests\Cms\MarketingPage\Admin\CreateMarketingPageRequest;

class CreateMarketingPageDTO
{
    public function __construct(
        public readonly MarketingPageTypeEnum $type,
        public readonly array $slug,
        public readonly array $title,
        public readonly array $sections,
        public readonly array $seo,
        public readonly MarketingPageStatusEnum $status,
        public readonly ?string $publishedAt,
        public readonly int $createdBy,
        public readonly int $updatedBy,
    ) {}

    public static function fromRequest(CreateMarketingPageRequest $request): self
    {
        return new self(
            type: MarketingPageTypeEnum::from($request->string('type')->toString()),
            slug: $request->array('slug'),
            title: $request->array('title'),
            sections: $request->array('sections'),
            seo: $request->array('seo'),
            status: MarketingPageStatusEnum::from($request->string('status')->toString()),
            publishedAt: $request->input('published_at'),
            createdBy: (int) $request->user()->id,
            updatedBy: (int) $request->user()->id,
        );
    }
}
