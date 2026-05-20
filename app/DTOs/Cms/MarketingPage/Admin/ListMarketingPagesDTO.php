<?php

declare(strict_types=1);

namespace App\DTOs\Cms\MarketingPage\Admin;

use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Enums\Cms\MarketingPage\MarketingPageTypeEnum;
use App\Http\Requests\Cms\MarketingPage\Admin\ListMarketingPagesRequest;

class ListMarketingPagesDTO
{
    public function __construct(
        public readonly ?MarketingPageTypeEnum $type,
        public readonly string|null $status,
        public readonly ?string $search,
        public readonly int $perPage,
    ) {}

    public static function fromRequest(ListMarketingPagesRequest $request): self
    {
        $type = $request->string('type')->toString();
        $status = $request->string('status')->toString();

        return new self(
            type: $type !== '' ? MarketingPageTypeEnum::from($type) : null,
            status: $status !== '' ? $status : null,
            search: $request->filled('search') ? $request->string('search')->toString() : null,
            perPage: $request->integer('per_page', 15),
        );
    }
}
