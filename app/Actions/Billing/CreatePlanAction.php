<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Billing\CreatePlanDTO;
use App\Enums\Entitlement\FeatureKeyEnum;
use App\Enums\ErrorCode;
use App\Enums\Subscription\PlanTierEnum;
use App\Models\Plan;
use App\Repositories\Billing\PlanPriceRepository;
use App\Repositories\Subscription\PlanRepository;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatePlanAction
{
    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly PlanPriceRepository $priceRepository,
        private readonly BillingProviderInterface $billingProvider,
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(CreatePlanDTO $dto, ?int $excludePlanId = null): Plan
    {
        $this->validate($dto, $excludePlanId);

        return DB::transaction(function () use ($dto) {
            // Create the plan
            $plan = Plan::create([
                'code' => $dto->code,
                'name' => $dto->name,
                'description' => $dto->description,
                'tier' => $dto->tier,
                'tier_rank' => $dto->tierRank,
                'is_public' => $dto->isPublic,
                'is_active' => $dto->isActive,
                'trial_days' => $dto->trialDays,
                'sort_order' => $dto->sortOrder,
                'metadata' => $dto->metadata,
            ]);

            // Create features
            foreach ($dto->features as $feature) {
                $plan->features()->create([
                    'feature_key' => $feature['featureKey'],
                    'value_type' => $feature['valueType'],
                    'limit_value' => $feature['limitValue'] ?? null,
                    'boolean_value' => $feature['booleanValue'] ?? null,
                ]);
            }

            // Create prices and corresponding Stripe prices
            foreach ($dto->prices as $priceData) {
                $planPrice = $plan->prices()->create([
                    'billing_cycle' => $priceData['billingCycle'],
                    'currency' => $priceData['currency'],
                    'amount_cents' => $priceData['amountCents'],
                    'provider' => $priceData['provider'] ?? 'stripe',
                    'is_active' => true,
                ]);

                // Create in billing provider (Stripe Product + Price)
                try {
                    $providerData = $this->billingProvider->createPrice($plan, $planPrice);
                    
                    // Update plan with provider product ID (once)
                    if (!$plan->provider_product_id && !empty($providerData['provider_product_id'])) {
                        $plan->update(['provider_product_id' => $providerData['provider_product_id']]);
                    }

                    // Update price with provider price ID
                    $planPrice->update(['provider_price_id' => $providerData['provider_price_id']]);
                } catch (\Exception $e) {
                    Log::channel('billing')->error('plan.create.provider_failed', [
                        'plan_id' => $plan->id,
                        'price_id' => $planPrice->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            // Audit log
            $this->auditLogger->record('platform.plan.created', [
                'resource_type' => 'plan',
                'resource_id' => $plan->id,
                'resource_name' => $plan->code,
                'tier' => $dto->tier,
                'tier_rank' => $dto->tierRank,
            ]);

            Log::channel('billing')->info('plan.created', [
                'plan_id' => $plan->id,
                'code' => $plan->code,
                'tier' => $plan->tier->value,
            ]);

            return $plan->fresh(['features', 'prices']);
        });
    }

    private function validate(CreatePlanDTO $dto, ?int $excludePlanId = null): void
    {
        // Validate code uniqueness among current plans
        if (!$this->planRepository->isCodeUniqueAmongCurrent($dto->code, $excludePlanId)) {
            throw new \DomainException(
                ErrorCode::BIL_014->value . ': Plan code already exists among current plans'
            );
        }

        // Validate tier is one of the enum values
        if (!in_array($dto->tier, PlanTierEnum::values(), true)) {
            throw new \DomainException(
                ErrorCode::BIL_020->value . ': Invalid tier value. Must be one of: ' . implode(', ', PlanTierEnum::values())
            );
        }

        // Validate tier_rank
        if ($dto->tierRank < 1) {
            throw new \DomainException(
                ErrorCode::BIL_021->value . ': tier_rank must be >= 1'
            );
        }

        // Validate at least one price
        if (empty($dto->prices)) {
            throw new \DomainException(
                ErrorCode::BIL_022->value . ': Plan must have at least one price'
            );
        }

        // Validate feature keys
        $validKeys = FeatureKeyEnum::values();
        foreach ($dto->features as $feature) {
            if (!in_array($feature['featureKey'], $validKeys, true)) {
                throw new \DomainException(
                    ErrorCode::BIL_023->value . ': Invalid feature key: ' . $feature['featureKey']
                );
            }

            $this->validateFeatureValueType($feature);
        }

        // Validate price amounts
        foreach ($dto->prices as $price) {
            if ($price['amountCents'] < 0) {
                throw new \DomainException('Price amount_cents must be >= 0');
            }
        }
    }

    private function validateFeatureValueType(array $feature): void
    {
        $validTypes = ['limit', 'boolean', 'unlimited'];
        if (!in_array($feature['valueType'], $validTypes, true)) {
            throw new \DomainException(
                ErrorCode::BIL_024->value . ': Invalid value_type. Must be one of: ' . implode(', ', $validTypes)
            );
        }

        // Validate type-specific requirements
        if ($feature['valueType'] === 'limit') {
            if (!isset($feature['limitValue']) || $feature['limitValue'] < 0) {
                throw new \DomainException(
                    ErrorCode::BIL_024->value . ': limit value_type requires limitValue >= 0'
                );
            }
        }

        if ($feature['valueType'] === 'boolean') {
            if (!isset($feature['booleanValue'])) {
                throw new \DomainException(
                    ErrorCode::BIL_024->value . ': boolean value_type requires booleanValue'
                );
            }
        }

        if ($feature['valueType'] === 'unlimited') {
            if (isset($feature['limitValue']) || isset($feature['booleanValue'])) {
                throw new \DomainException(
                    ErrorCode::BIL_024->value . ': unlimited value_type must not have limitValue or booleanValue'
                );
            }
        }
    }
}
