<?php

declare(strict_types=1);

namespace App\DTOs\Lead;

use App\Http\Requests\Admin\Lead\GetLeadRequest;

class GetLeadDTO
{
    public function __construct(
        public readonly int $id,
    ) {}

    public static function fromRequest(
        GetLeadRequest $request,
        int $id,
    ): self {
        return new self(
            id: (int) ($request->route('lead') ?? $id)
        );
    }
}
