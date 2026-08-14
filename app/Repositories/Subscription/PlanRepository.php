<?php

namespace App\Repositories\Subscription;

use App\Models\Plan;
use App\Models\Subscription;
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
     * Find current (non-superseded) plan by code.
     * This is the correct method to use when resolving plan codes for checkout/upgrades.
     * 
     * Defense in depth: uses latest('id') ordering even though we expect only one
     * non-superseded row per code. This ensures deterministic behavior if multiple
     * current versions exist due to a future bug.
     */
    public function findCurrentByCode(string $code): ?Plan
    {
        return Plan::where('code', $code)
            ->whereNull('superseded_by_plan_id')
            ->latest('id')
            ->first();
    }

    /**
     * Find current plan by code or fail.
     */
    public function findCurrentByCodeOrFail(string $code): Plan
    {
        $plan = $this->findCurrentByCode($code);
        
        if (!$plan) {
            throw new \DomainException("No current plan found for code: {$code}");
        }
        
        return $plan;
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

    /**
     * List all plans with optional filters.
     */
    public function listAll(array $filters = []): Collection
    {
        $query = Plan::query();

        // Default: only current versions unless explicitly requested
        if (!isset($filters['include_superseded']) || !$filters['include_superseded']) {
            $query->whereNull('superseded_by_plan_id');
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        if (isset($filters['tier'])) {
            $query->where('tier', $filters['tier']);
        }

        // Search in plan code and name (JSON column)
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name->en', 'like', "%{$search}%")
                  ->orWhere('name->ar', 'like', "%{$search}%");
            });
        }

        return $query->with(['prices', 'features'])
            ->orderBy('sort_order')
            ->orderBy('tier_rank')
            ->get();
    }

    /**
     * Check if a plan is in use by any subscription (any status).
     */
    public function isInUse(int $planId): bool
    {
        return Subscription::where('plan_id', $planId)
            ->orWhere('pending_plan_id', $planId)
            ->exists();
    }

    /**
     * Check if a plan has active subscribers.
     * Narrower check using only subscriptions that grant access.
     */
    public function hasActiveSubscribers(int $planId): bool
    {
        return Subscription::withAccess()
            ->where(function ($query) use ($planId) {
                $query->where('plan_id', $planId)
                    ->orWhere('pending_plan_id', $planId);
            })
            ->exists();
    }

    /**
     * Check if a code is unique among current (non-superseded) plans.
     * Used to enforce application-level uniqueness constraint.
     */
    public function isCodeUniqueAmongCurrent(string $code, ?int $excludePlanId = null): bool
    {
        $query = Plan::where('code', $code)
            ->whereNull('superseded_by_plan_id');

        if ($excludePlanId) {
            $query->where('id', '!=', $excludePlanId);
        }

        return !$query->exists();
    }
}
