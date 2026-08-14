<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Billing\CreatePlanDTO;
use App\DTOs\Billing\UpdatePlanDTO;
use App\Enums\ErrorCode;
use App\Models\Plan;
use App\Repositories\Subscription\PlanRepository;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePlanAction
{
    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly CreatePlanAction $createPlanAction,
        private readonly BillingProviderInterface $billingProvider,
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Update a plan. If the plan is in use and the update contains breaking changes,
     * a new version is created and the old one is marked as superseded.
     * 
     * @return Plan The updated plan (may be a new plan ID if versioned)
     */
    public function execute(UpdatePlanDTO $dto): Plan
    {
        $plan = $this->planRepository->findByIdOrFail($dto->planId);
        $isInUse = $this->planRepository->isInUse($dto->planId);

        // If plan is in use AND update has breaking changes, version it
        if ($isInUse && $dto->hasBreakingChanges()) {
            return $this->createNewVersion($plan, $dto);
        }

        // Otherwise, update in place
        return $this->updateInPlace($plan, $dto);
    }

    private function updateInPlace(Plan $plan, UpdatePlanDTO $dto): Plan
    {
        return DB::transaction(function () use ($plan, $dto) {
            $updates = [];

            // Non-breaking fields that can always be updated in place
            if ($dto->name !== null) {
                $updates['name'] = $dto->name;
            }
            if ($dto->description !== null) {
                $updates['description'] = $dto->description;
            }
            if ($dto->sortOrder !== null) {
                $updates['sort_order'] = $dto->sortOrder;
            }
            if ($dto->metadata !== null) {
                $updates['metadata'] = $dto->metadata;
            }
            if ($dto->trialDays !== null) {
                $updates['trial_days'] = $dto->trialDays;
            }
            if ($dto->isPublic !== null) {
                $updates['is_public'] = $dto->isPublic;
            }
            if ($dto->isActive !== null) {
                $updates['is_active'] = $dto->isActive;
            }

            // Breaking fields - only allowed if plan is NOT in use
            if ($dto->code !== null) {
                $updates['code'] = $dto->code;
            }
            if ($dto->tier !== null) {
                $updates['tier'] = $dto->tier;
            }
            if ($dto->tierRank !== null) {
                $updates['tier_rank'] = $dto->tierRank;
            }

            if (!empty($updates)) {
                $plan->update($updates);
            }

            // Update features if provided (only if not in use)
            if ($dto->features !== null) {
                $plan->features()->delete();
                foreach ($dto->features as $feature) {
                    $plan->features()->create([
                        'feature_key' => $feature['featureKey'],
                        'value_type' => $feature['valueType'],
                        'limit_value' => $feature['limitValue'] ?? null,
                        'boolean_value' => $feature['booleanValue'] ?? null,
                    ]);
                }
            }

            // Update prices if provided (only if not in use)
            if ($dto->prices !== null) {
                $plan->prices()->update(['is_active' => false]);
                foreach ($dto->prices as $priceData) {
                    $planPrice = $plan->prices()->create([
                        'billing_cycle' => $priceData['billingCycle'],
                        'currency' => $priceData['currency'],
                        'amount_cents' => $priceData['amountCents'],
                        'provider' => $priceData['provider'] ?? 'stripe',
                        'is_active' => true,
                    ]);

                    $providerData = $this->billingProvider->createPrice($plan, $planPrice);
                    $planPrice->update(['provider_price_id' => $providerData['provider_price_id']]);
                }
            }

            $this->auditLogger->record('platform.plan.updated', [
                'resource_type' => 'plan',
                'resource_id' => $plan->id,
                'resource_name' => $plan->code,
                'updates' => array_keys($updates),
            ]);

            Log::channel('billing')->info('plan.updated', [
                'plan_id' => $plan->id,
                'code' => $plan->code,
            ]);

            return $plan->fresh(['features', 'prices']);
        });
    }

    private function createNewVersion(Plan $oldPlan, UpdatePlanDTO $dto): Plan
    {
        return DB::transaction(function () use ($oldPlan, $dto) {
            // Prepare data for new version by merging old + new
            $newPlanData = new CreatePlanDTO(
                code: $dto->code ?? $oldPlan->code,
                name: $dto->name ?? $oldPlan->name,
                description: $dto->description ?? $oldPlan->description,
                tier: $dto->tier ?? $oldPlan->tier->value,
                tierRank: $dto->tierRank ?? $oldPlan->tier_rank,
                isPublic: $dto->isPublic ?? $oldPlan->is_public,
                isActive: $dto->isActive ?? $oldPlan->is_active,
                trialDays: $dto->trialDays ?? $oldPlan->trial_days,
                sortOrder: $dto->sortOrder ?? $oldPlan->sort_order,
                metadata: $dto->metadata ?? $oldPlan->metadata,
                features: $dto->features ?? $this->serializeFeatures($oldPlan),
                prices: $dto->prices ?? $this->serializePrices($oldPlan),
            );

            // Create new version (exclude old plan from uniqueness check)
            $newPlan = $this->createPlanAction->execute($newPlanData, $oldPlan->id);

            // Mark old plan as superseded
            $oldPlan->update([
                'superseded_by_plan_id' => $newPlan->id,
                'is_active' => false,
                'is_public' => false,
            ]);

            $this->auditLogger->record('platform.plan.versioned', [
                'resource_type' => 'plan',
                'resource_id' => $newPlan->id,
                'resource_name' => $newPlan->code,
                'old_plan_id' => $oldPlan->id,
                'reason' => 'breaking_changes_while_in_use',
            ]);

            Log::channel('billing')->info('plan.versioned', [
                'old_plan_id' => $oldPlan->id,
                'new_plan_id' => $newPlan->id,
                'code' => $newPlan->code,
            ]);

            return $newPlan;
        });
    }

    private function serializeFeatures(Plan $plan): array
    {
        return $plan->features->map(function ($feature) {
            return [
                'featureKey' => $feature->feature_key->value,
                'valueType' => $feature->value_type,
                'limitValue' => $feature->limit_value,
                'booleanValue' => $feature->boolean_value,
            ];
        })->toArray();
    }

    private function serializePrices(Plan $plan): array
    {
        return $plan->prices()->where('is_active', true)->get()->map(function ($price) {
            return [
                'billingCycle' => $price->billing_cycle->value,
                'currency' => $price->currency,
                'amountCents' => $price->amount_cents,
                'provider' => $price->provider,
            ];
        })->toArray();
    }
}
