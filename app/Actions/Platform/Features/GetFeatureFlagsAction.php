<?php

declare(strict_types=1);

namespace App\Actions\Platform\Features;

use App\Support\FeatureFlags\FeatureFlag;

class GetFeatureFlagsAction
{
    /**
     * Get all feature flags with their metadata and runtime values.
     *
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        $allFlags = FeatureFlag::all();
        $enrichedFlags = [];

        foreach ($allFlags as $name => $config) {
            $enrichedFlags[] = [
                'name' => $name,
                'value' => FeatureFlag::value($name),
                'has_override' => FeatureFlag::hasOverride($name),
                'updated_at' => FeatureFlag::updatedAt($name),
                'metadata' => is_array($config) ? $config : ['default' => $config],
            ];
        }

        return $enrichedFlags;
    }
}
