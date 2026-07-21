<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Marketing\Platform\Admin;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Http\Requests\Cms\Marketing\Platform\Admin\CreatePlatformMarketingPageRequest;

class CreatePlatformMarketingPageDTO
{
    public function __construct(
        public readonly ?\App\Enums\Cms\MarketingPage\MarketingPageTypeEnum $type,
        public readonly array $title,
        public readonly array $slug,
        public readonly ?array $excerpt,
        public readonly array $content,
        public readonly MarketingPageStatusEnum $status,
        public readonly ?string $publishedAt,
        public readonly ?array $seo,
        public readonly ?MarketingPageTemplateEnum $template,
        public readonly int $sortOrder,
        public readonly int $createdBy,
        public readonly int $updatedBy,
    ) {}

    public static function fromRequest(CreatePlatformMarketingPageRequest $request): self
    {
        return new self(
            type: $request->has('type') ? \App\Enums\Cms\MarketingPage\MarketingPageTypeEnum::from($request->string('type')->toString()) : null,
            title: $request->array('title'),
            slug: $request->array('slug'),
            excerpt: $request->array('excerpt'),
            content: $request->array('content'),
            status: MarketingPageStatusEnum::from($request->string('status')->toString()),
            publishedAt: $request->input('published_at'),
            seo: $request->array('seo'),
            template: $request->has('template') ? MarketingPageTemplateEnum::from($request->string('template')->toString()) : null,
            sortOrder: $request->integer('sort_order', 0),
            createdBy: (int) $request->user()->id,
            updatedBy: (int) $request->user()->id,
        );
    }
}
