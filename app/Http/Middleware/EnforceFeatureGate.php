<?php

namespace App\Http\Middleware;

use App\Enums\Entitlement\FeatureKeyEnum;
use App\Enums\ErrorCode;
use App\Models\Store;
use App\Services\Entitlement\FeatureGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceFeatureGate
{
    public function __construct(
        private FeatureGateService $featureGateService,
    ) {}

    /**
     * Handle an incoming request.
     * 
     * Usage: Route::middleware('feature.gate:analytics.advanced')
     * 
     * Ensures the store has access to a specific feature.
     * Returns 402 Payment Required if feature is not available on current plan.
     * 
     * @param string $featureKey Format: 'analytics.advanced' or 'products.max'
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $store = $request->route('store');
        $storeId = $store instanceof Store ? $store->id : null;

        if (!$storeId) {
            return response()->json([
                'status' => false,
                'message' => 'Store ID is required.',
                'error_code' => ErrorCode::VAL_001->value,
            ], 400);
        }

        // Convert feature key string to enum
        try {
            $featureEnum = FeatureKeyEnum::from($featureKey);
        } catch (\ValueError) {
            return response()->json([
                'status' => false,
                'message' => "Invalid feature key: {$featureKey}",
                'error_code' => ErrorCode::VAL_001->value,
            ], 400);
        }

        // Check if feature is available
        if (!$this->featureGateService->hasFeature($storeId, $featureEnum)) {
            return response()->json([
                'status' => false,
                'message' => "This feature is not available on your current plan.",
                'error_code' => ErrorCode::SUB_002->value, // Feature not available
                'meta' => [
                    'feature' => $featureKey,
                    'upgrade_required' => true,
                    'upgrade_url' => '/billing/upgrade', // TODO: Make configurable
                ],
            ], 402); // 402 Payment Required
        }

        return $next($request);
    }
}
