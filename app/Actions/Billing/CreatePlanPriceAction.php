<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Billing\CreatePlanPriceDTO;
use App\Models\PlanPrice;
use App\Repositories\Billing\PlanPriceRepository;
use App\Repositories\Subscription\PlanRepository;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatePlanPriceAction
{
    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly PlanPriceRepository $priceRepository,
        private readonly BillingProviderInterface $billingProvider,
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Create a new price for a plan.
     * If an active price already exists for the same criteria, it is deactivated.
     */
    public function execute(CreatePlanPriceDTO $dto): PlanPrice
    {
        $plan = $this->planRepository->findByIdOrFail($dto->planId);

        return DB::transaction(function () use ($plan, $dto) {
            // Check if an active price already exists
            $existingPrice = $this->priceRepository->findActivePrice(
                $dto->planId,
                $dto->billingCycle,
                $dto->currency,
                $dto->provider
            );

            // Deactivate the existing price if found
            if ($existingPrice) {
                $this->priceRepository->deactivate($existingPrice->id);
                
                // Archive in billing provider too
                if ($existingPrice->provider_price_id) {
                    try {
                        $this->billingProvider->archivePrice($existingPrice->provider_price_id);
                    } catch (\Exception $e) {
                        Log::channel('billing')->warning('provider.archive_price.failed', [
                            'price_id' => $existingPrice->id,
                            'provider_price_id' => $existingPrice->provider_price_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Log::channel('billing')->info('plan_price.deactivated', [
                    'price_id' => $existingPrice->id,
                    'plan_id' => $dto->planId,
                ]);
            }

            // Create the new price locally
            $planPrice = $this->priceRepository->create([
                'plan_id' => $dto->planId,
                'billing_cycle' => $dto->billingCycle,
                'currency' => $dto->currency,
                'amount_cents' => $dto->amountCents,
                'provider' => $dto->provider,
                'is_active' => true,
            ]);

            // Create in billing provider
            try {
                $providerData = $this->billingProvider->createPrice($plan, $planPrice);
                $planPrice->update(['provider_price_id' => $providerData['provider_price_id']]);
                
                // Update plan's provider_product_id if not set
                if (!$plan->provider_product_id && !empty($providerData['provider_product_id'])) {
                    $plan->update(['provider_product_id' => $providerData['provider_product_id']]);
                }
            } catch (\Exception $e) {
                Log::channel('billing')->error('plan_price.create.provider_failed', [
                    'plan_id' => $dto->planId,
                    'price_id' => $planPrice->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            $this->auditLogger->record('platform.plan.price_created', [
                'resource_type' => 'plan_price',
                'resource_id' => $planPrice->id,
                'plan_id' => $dto->planId,
                'plan_code' => $plan->code,
                'amount_cents' => $dto->amountCents,
                'billing_cycle' => $dto->billingCycle,
            ]);

            Log::channel('billing')->info('plan_price.created', [
                'price_id' => $planPrice->id,
                'plan_id' => $dto->planId,
                'amount_cents' => $dto->amountCents,
            ]);

            return $planPrice->fresh();
        });
    }
}
