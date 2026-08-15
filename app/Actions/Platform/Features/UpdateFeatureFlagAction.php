<?php

declare(strict_types=1);

namespace App\Actions\Platform\Features;

use App\DTOs\Platform\Features\UpdateFeatureFlagDTO;
use App\Support\FeatureFlags\FeatureFlag;

class UpdateFeatureFlagAction
{
    /**
     * Update a feature flag's runtime value.
     *
     * @return array<string, mixed>
     */
    public function execute(UpdateFeatureFlagDTO $dto): array
    {
        // Set the new value in cache
        FeatureFlag::setValue($dto->feature, $dto->value);

        // Return enriched flag data
        $config = FeatureFlag::metadata($dto->feature);

        return [
            'name' => $dto->feature,
            'value' => FeatureFlag::value($dto->feature),
            'has_override' => FeatureFlag::hasOverride($dto->feature),
            'updated_at' => FeatureFlag::updatedAt($dto->feature),
            'metadata' => $config ?? ['default' => FeatureFlag::value($dto->feature)],
        ];
    }
}
