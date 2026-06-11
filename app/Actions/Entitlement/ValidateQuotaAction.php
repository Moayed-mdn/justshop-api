<?php

namespace App\Actions\Entitlement;

use App\Enums\Entitlement\FeatureKeyEnum;
use App\Exceptions\Entitlement\QuotaExceededException;
use App\Services\Entitlement\FeatureGateService;

class ValidateQuotaAction
{
    public function __construct(
        private FeatureGateService $featureGateService,
    ) {}

    /**
     * Validate that a store has not exceeded quota for a feature.
     * 
     * @throws QuotaExceededException
     */
    public function execute(int $storeId, FeatureKeyEnum $feature, int $currentCount): void
    {
        $this->featureGateService->ensureQuota($storeId, $feature, $currentCount);
    }

    /**
     * Check if quota is available (non-throwing version).
     * 
     * Returns true if under limit, false if at or over limit.
     */
    public function check(int $storeId, FeatureKeyEnum $feature, int $currentCount): bool
    {
        try {
            $this->execute($storeId, $feature, $currentCount);
            return true;
        } catch (QuotaExceededException) {
            return false;
        }
    }

    /**
     * Get remaining quota for a feature.
     * 
     * Returns null if unlimited.
     */
    public function getRemainingQuota(int $storeId, FeatureKeyEnum $feature): ?int
    {
        $limit = $this->featureGateService->getFeatureLimit($storeId, $feature);
        
        if ($limit === null) {
            return null; // Unlimited
        }

        $currentUsage = $this->featureGateService->getCurrentUsage($storeId, $feature) ?? 0;
        
        return max(0, $limit - $currentUsage);
    }
}
