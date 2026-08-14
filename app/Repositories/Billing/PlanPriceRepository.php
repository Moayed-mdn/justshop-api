<?php

namespace App\Repositories\Billing;

use App\Models\PlanPrice;

class PlanPriceRepository
{
    /**
     * Find an active price for a plan with specific criteria.
     */
    public function findActivePrice(
        int $planId,
        string $billingCycle,
        string $currency,
        string $provider = 'stripe'
    ): ?PlanPrice {
        return PlanPrice::where('plan_id', $planId)
            ->where('billing_cycle', $billingCycle)
            ->where('currency', $currency)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if an active price exists for the given criteria.
     * Used to enforce the application-level uniqueness constraint.
     */
    public function hasActivePrice(
        int $planId,
        string $billingCycle,
        string $currency,
        string $provider = 'stripe',
        ?int $excludePriceId = null
    ): bool {
        $query = PlanPrice::where('plan_id', $planId)
            ->where('billing_cycle', $billingCycle)
            ->where('currency', $currency)
            ->where('provider', $provider)
            ->where('is_active', true);

        if ($excludePriceId) {
            $query->where('id', '!=', $excludePriceId);
        }

        return $query->exists();
    }

    /**
     * Deactivate an existing active price.
     */
    public function deactivate(int $priceId): void
    {
        PlanPrice::where('id', $priceId)->update(['is_active' => false]);
    }

    /**
     * Create a new price.
     */
    public function create(array $data): PlanPrice
    {
        return PlanPrice::create($data);
    }

    /**
     * Find price by ID.
     */
    public function findById(int $priceId): ?PlanPrice
    {
        return PlanPrice::find($priceId);
    }

    /**
     * Find price by ID or fail.
     */
    public function findByIdOrFail(int $priceId): PlanPrice
    {
        return PlanPrice::findOrFail($priceId);
    }
}
