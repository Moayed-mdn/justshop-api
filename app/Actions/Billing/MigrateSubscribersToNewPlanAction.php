<?php

namespace App\Actions\Billing;

use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\DTOs\Billing\MigrateSubscribersDTO;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\ErrorCode;
use App\Models\BillingAccount;
use App\Models\Store;
use App\Models\Subscription;
use App\Repositories\Subscription\PlanRepository;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateSubscribersToNewPlanAction
{
    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly RecomputeEntitlementsAction $recomputeEntitlements,
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Migrate subscribers from one plan to another.
     * 
     * @return array In dry_run mode: analysis of impact. In real mode: migrated account IDs.
     */
    public function execute(MigrateSubscribersDTO $dto): array
    {
        $this->validate($dto);

        $fromPlan = $this->planRepository->findByIdOrFail($dto->fromPlanId);
        $toPlan = $this->planRepository->findByIdOrFail($dto->toPlanId);

        // Get subscriptions to migrate
        $subscriptions = Subscription::withAccess()
            ->whereIn('billing_account_id', $dto->billingAccountIds)
            ->where('plan_id', $dto->fromPlanId)
            ->with(['billingAccount'])
            ->get();

        if ($dto->dryRun) {
            return $this->performDryRun($subscriptions, $fromPlan, $toPlan);
        }

        return $this->performMigration($subscriptions, $fromPlan, $toPlan, $dto->grandfatherExisting);
    }

    private function validate(MigrateSubscribersDTO $dto): void
    {
        // Validate target plan exists and is active
        $toPlan = $this->planRepository->findById($dto->toPlanId);
        if (!$toPlan || !$toPlan->is_active) {
            throw new \DomainException(
                ErrorCode::BIL_017->value . ': Migration target plan not found or inactive'
            );
        }

        // Prevent migrating to same plan
        if ($dto->fromPlanId === $dto->toPlanId) {
            throw new \DomainException(
                ErrorCode::BIL_018->value . ': Cannot migrate to the same plan'
            );
        }

        // Require at least one billing account
        if (empty($dto->billingAccountIds)) {
            throw new \DomainException(
                ErrorCode::BIL_019->value . ': No billing accounts provided for migration'
            );
        }
    }

    private function performDryRun(Collection $subscriptions, $fromPlan, $toPlan): array
    {
        $toPlan->load('features');
        $toFeatures = $this->extractFeatureLimits($toPlan);

        $analysis = [];

        foreach ($subscriptions as $subscription) {
            $billingAccount = $subscription->billingAccount;
            $owner = $billingAccount->owner;

            // Get current usage
            $storesCount = Store::where('owner_id', $owner->id)->count();
            $productsCount = Store::where('owner_id', $owner->id)
                ->withCount('products')
                ->get()
                ->sum('products_count');

            // Check against new limits
            $wouldExceed = [];
            if (isset($toFeatures['stores.max']) && $toFeatures['stores.max'] !== null && $storesCount > $toFeatures['stores.max']) {
                $wouldExceed[] = [
                    'feature' => 'stores.max',
                    'current' => $storesCount,
                    'new_limit' => $toFeatures['stores.max'],
                ];
            }
            if (isset($toFeatures['products.max']) && $toFeatures['products.max'] !== null && $productsCount > $toFeatures['products.max']) {
                $wouldExceed[] = [
                    'feature' => 'products.max',
                    'current' => $productsCount,
                    'new_limit' => $toFeatures['products.max'],
                ];
            }

            $analysis[] = [
                'billing_account_id' => $billingAccount->id,
                'subscription_id' => $subscription->id,
                'owner_email' => $owner->email,
                'current_usage' => [
                    'stores' => $storesCount,
                    'products' => $productsCount,
                ],
                'new_limits' => [
                    'stores' => $toFeatures['stores.max'] ?? 'unlimited',
                    'products' => $toFeatures['products.max'] ?? 'unlimited',
                ],
                'would_exceed' => $wouldExceed,
                'has_conflicts' => !empty($wouldExceed),
            ];
        }

        Log::channel('billing')->info('plan_migration.dry_run', [
            'from_plan_id' => $fromPlan->id,
            'to_plan_id' => $toPlan->id,
            'accounts_analyzed' => count($analysis),
            'conflicts' => collect($analysis)->where('has_conflicts', true)->count(),
        ]);

        return [
            'dry_run' => true,
            'from_plan' => ['id' => $fromPlan->id, 'code' => $fromPlan->code],
            'to_plan' => ['id' => $toPlan->id, 'code' => $toPlan->code],
            'total_accounts' => count($analysis),
            'accounts_with_conflicts' => collect($analysis)->where('has_conflicts', true)->count(),
            'analysis' => $analysis,
        ];
    }

    private function performMigration(Collection $subscriptions, $fromPlan, $toPlan, bool $grandfather): array
    {
        $migrated = [];

        DB::transaction(function () use ($subscriptions, $toPlan, $grandfather, &$migrated) {
            foreach ($subscriptions as $subscription) {
                $oldPlanId = $subscription->plan_id;

                // Update subscription to new plan
                $subscription->update(['plan_id' => $toPlan->id]);

                // Recompute entitlements for all stores owned by this billing account
                $stores = Store::where('owner_id', $subscription->billingAccount->owner_user_id)->get();

                foreach ($stores as $store) {
                    $this->recomputeEntitlements->execute(
                        new RecomputeEntitlementsDTO(
                            billingAccountId: $subscription->billing_account_id,
                            storeId: $store->id,
                            isGrandfathered: $grandfather,
                        )
                    );
                }

                $this->auditLogger->record('platform.plan.subscribers_migrated', [
                    'resource_type' => 'subscription',
                    'resource_id' => $subscription->id,
                    'billing_account_id' => $subscription->billing_account_id,
                    'from_plan_id' => $oldPlanId,
                    'to_plan_id' => $toPlan->id,
                    'grandfathered' => $grandfather,
                ]);

                $migrated[] = $subscription->billing_account_id;
            }
        });

        Log::channel('billing')->info('plan_migration.completed', [
            'from_plan_id' => $fromPlan->id,
            'to_plan_id' => $toPlan->id,
            'accounts_migrated' => count($migrated),
            'grandfathered' => $grandfather,
        ]);

        return [
            'dry_run' => false,
            'from_plan' => ['id' => $fromPlan->id, 'code' => $fromPlan->code],
            'to_plan' => ['id' => $toPlan->id, 'code' => $toPlan->code],
            'migrated_billing_account_ids' => $migrated,
            'total_migrated' => count($migrated),
            'grandfathered' => $grandfather,
        ];
    }

    private function extractFeatureLimits($plan): array
    {
        $limits = [];
        foreach ($plan->features as $feature) {
            $key = $feature->feature_key->value;
            $limits[$key] = match ($feature->value_type) {
                'limit' => $feature->limit_value,
                'unlimited' => null,
                'boolean' => $feature->boolean_value,
                default => null,
            };
        }
        return $limits;
    }
}
