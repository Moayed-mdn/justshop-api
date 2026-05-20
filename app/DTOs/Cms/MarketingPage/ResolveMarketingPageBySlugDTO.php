<?php

declare(strict_types=1);

namespace App\DTOs\Cms\MarketingPage;

use App\Http\Requests\Cms\MarketingPage\GetMarketingPageRequest;

class ResolveMarketingPageBySlugDTO
{
    public function __construct(
        public readonly string $slug,
        public readonly string $locale,
        public readonly string $fallbackLocale,
    ) {}

    public static function fromRequest(
        GetMarketingPageRequest $request,
        string $slug,
    ): self {
        return new self(
            slug: $slug,
            locale: $request->string('locale')->toString() ?: config('content.default_locale', 'en'),
            fallbackLocale: config('content.default_locale', 'en'),
        );
    }
}
