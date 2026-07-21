<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Marketing\Platform\Admin;

use App\Http\Requests\Cms\Marketing\Platform\Admin\ListPlatformMarketingPagesRequest;

class ListPlatformMarketingPagesDTO
{
    public function __construct(
        public readonly ?string $type,
        public readonly string|null $status,
        public readonly ?string $search,
        public readonly int $perPage,
    ) {}

    public static function fromRequest(ListPlatformMarketingPagesRequest $request): self
    {
        return new self(
            type: $request->filled('type') ? $request->string('type')->toString() : null,
            status: $request->string('status')->toString() ?: null,
            search: $request->filled('search') ? $request->string('search')->toString() : null,
            perPage: $request->integer('per_page', 15),
        );
    }
}
