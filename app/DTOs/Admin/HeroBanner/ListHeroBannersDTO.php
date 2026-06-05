<?php

declare(strict_types=1);

namespace App\DTOs\Admin\HeroBanner;

use Illuminate\Http\Request;

class ListHeroBannersDTO
{
    public function __construct(
        public int $storeId,
        public ?string $status = null,
        public ?string $search = null,
    ) {}

    public static function fromRequest(Request $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            status: $request->string('status')->toString() ?: null,
            search: $request->string('search')->toString() ?: null,
        );
    }
}
