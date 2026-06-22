<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Marketing\Store\Admin;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Http\Requests\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageRequest;

class UpdateStoreMarketingPageDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $storeId,
        public readonly array $title,
        public readonly array $slug,
        public readonly ?array $excerpt,
        public readonly ?array $content,
        public readonly MarketingPageStatusEnum $status,
        public readonly ?string $publishedAt,
        public readonly ?array $seo,
        public readonly ?MarketingPageTemplateEnum $template,
        public readonly int $sortOrder,
        public readonly bool $isHomepage,
        public readonly ?array $sections,
        public readonly int $updatedBy,
    ) {}

    public static function fromRequest(UpdateStoreMarketingPageRequest $request, int $storeId, int $id): self
    {
        $templateValue = $request->input('template');

        return new self(
            id: $id,
            storeId: $storeId,
            title: $request->array('title'),
            slug: $request->array('slug'),
            excerpt: $request->has('excerpt') ? $request->array('excerpt') : null,
            content: $request->has('content') ? $request->array('content') : null,
            status: MarketingPageStatusEnum::from($request->string('status')->toString()),
            publishedAt: $request->input('published_at'),
            seo: $request->has('seo') ? $request->array('seo') : null,
            template: $templateValue ? MarketingPageTemplateEnum::from((string) $templateValue) : null,
            sortOrder: $request->integer('sort_order', 0),
            isHomepage: $request->boolean('is_homepage', false),
            sections: $request->has('sections') ? $request->array('sections') : null,
            updatedBy: (int) $request->user()->id,
        );
    }
}
