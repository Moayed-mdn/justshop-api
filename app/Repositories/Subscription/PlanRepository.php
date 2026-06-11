<?php

namespace App\Repositories\Subscription;

use App\Models\Plan;
use Illuminate\Support\Collection;

class PlanRepository
{
    /**
     * Find plan by ID.
     */
    public function findById(int $planId): ?Plan
    {
        return Plan::find($planId);
    }

    /**
     * Find plan by ID or fail.
     */
    public function findByIdOrFail(int $planId): Plan
    {
        return Plan::findOrFail($planId);
    }

    /**
     * Find plan by code.
     */
    public function findByCode(string $code): ?Plan
    {
        return Plan::where('code', $code)->first();
    }

    /**
     * Find plan by code or fail.
     */
    public function findByCodeOrFail(string $code): Plan
    {
        return Plan::where('code', $code)->firstOrFail();
    }

    /**
     * Get all active plans.
     */
    public function getAllActive(): Collection
    {
        return Plan::active()
            ->orderBy('sort_order')
            ->with(['prices', 'features'])
            ->get();
    }

    /**
     * Get all public plans.
     */
    public function getAllPublic(): Collection
    {
        return Plan::active()
            ->public()
            ->orderBy('sort_order')
            ->with(['prices', 'features'])
            ->get();
    }

    /**
     * Get plan with relationships loaded.
     */
    public function findWithRelations(int $planId): ?Plan
    {
        return Plan::with(['prices', 'features'])
            ->find($planId);
    }
}
