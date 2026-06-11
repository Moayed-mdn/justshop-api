<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Get all public plans with pricing.
     * 
     * GET /api/v1/public/plans
     */
    public function index(Request $request): JsonResponse
    {
        $currency = $request->query('currency', 'USD');

        $plans = Plan::active()
            ->public()
            ->with([
                'prices' => fn($q) => $q->where('currency', $currency)->where('is_active', true),
                'features',
            ])
            ->orderBy('sort_order')
            ->get();

        return $this->success([
            'plans' => $plans,
            'currency' => $currency,
        ]);
    }

    /**
     * Get a single plan by code.
     * 
     * GET /api/v1/public/plans/{code}
     */
    public function show(string $code, Request $request): JsonResponse
    {
        $currency = $request->query('currency', 'USD');

        $plan = Plan::active()
            ->public()
            ->where('code', $code)
            ->with([
                'prices' => fn($q) => $q->where('currency', $currency)->where('is_active', true),
                'features',
            ])
            ->firstOrFail();

        return $this->success([
            'plan' => $plan,
            'currency' => $currency,
        ]);
    }
}
