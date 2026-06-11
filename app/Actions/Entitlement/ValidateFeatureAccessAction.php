<?php

namespace App\Actions\Entitlement;

use App\Enums\Entitlement\FeatureKeyEnum;
use App\Exceptions\Entitlement\FeatureNotAvailableException;
use App\Services\Entitlement\FeatureGateService;

class ValidateFeatureAccessAction
{
    public function __construct(
        private FeatureGateService $featureGateService,
    ) {}

    /**
     * Validate that a store has access to a specific feature.
     * 
     * @throws FeatureNotAvailableException
     */
    public function execute(int $storeId, FeatureKeyEnum $feature): void
    {
        if (!$this->featureGateService->hasFeature($storeId, $feature)) {
            throw new FeatureNotAvailableException(
                "The feature '{$feature->value}' is not available on your current plan."
            );
        }
    }

    /**
     * Check if a feature is available (non-throwing version).
     */
    public function check(int $storeId, FeatureKeyEnum $feature): bool
    {
        return $this->featureGateService->hasFeature($storeId, $feature);
    }
}
