<?php

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use App\Models\Store;
use App\Services\Entitlement\FeatureGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(
        private FeatureGateService $featureGateService,
    ) {}

    /**
     * Handle an incoming request.
     * 
     * Ensures the store has an active subscription that grants write access.
     * Returns 402 Payment Required if subscription is inactive or expired.
     */
    public function handle(Request $request, Closure $next): Response
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

        try {
            // Check write access (will throw if blocked)
            $this->featureGateService->ensureWriteAccess($storeId);

            return $next($request);
        } catch (\App\Exceptions\Subscription\SubscriptionRequiredException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'meta' => [
                    'subscription_required' => true,
                    'upgrade_url' => '/billing/upgrade', // TODO: Make configurable
                ],
            ], 402); // 402 Payment Required
        }
    }
}
