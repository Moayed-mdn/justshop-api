<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Features;

use App\Http\Requests\Platform\Features\UpdateFeatureFlagRequest;

class UpdateFeatureFlagDTO
{
    public function __construct(
        public readonly string $feature,
        public readonly mixed $value,
    ) {}

    public static function fromRequest(UpdateFeatureFlagRequest $request, string $feature): self
    {
        return new self(
            feature: $feature,
            value: $request->validated('value'),
        );
    }
}
