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
     * 
     * For API requests: Returns JSON response with 402 status
     * For browser requests: Can optionally redirect (based on Accept header)
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
            // If request expects JSON (API call from SPA), return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'error_code' => $e->getErrorCode(),
                    'meta' => [
                        'subscription_required' => true,
                        'upgrade_url' => config('app.frontend_url') . '/merchant/billing/plans',
                    ],
                ], 402); // 402 Payment Required
            }

            // For traditional browser requests (rare case), redirect with flash message
            return redirect()
                ->to(config('app.frontend_url') . '/merchant/billing/plans')
                ->with('error', $e->getMessage());
        }
    }
}
