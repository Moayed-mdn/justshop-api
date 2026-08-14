<?php

namespace App\Http\Controllers\Api\Platform\Billing;

use App\Actions\Billing\ArchivePlanAction;
use App\Actions\Billing\CreatePlanAction;
use App\Actions\Billing\CreatePlanPriceAction;
use App\Actions\Billing\DeletePlanAction;
use App\Actions\Billing\MigrateSubscribersToNewPlanAction;
use App\Actions\Billing\UpdatePlanAction;
use App\DTOs\Billing\CreatePlanDTO;
use App\DTOs\Billing\CreatePlanPriceDTO;
use App\DTOs\Billing\MigrateSubscribersDTO;
use App\DTOs\Billing\UpdatePlanDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Billing\MigrateSubscribersRequest;
use App\Http\Requests\Platform\Billing\StorePlanPriceRequest;
use App\Http\Requests\Platform\Billing\StorePlanRequest;
use App\Http\Requests\Platform\Billing\UpdatePlanRequest;
use App\Repositories\Billing\PlanPriceRepository;
use App\Repositories\Subscription\PlanRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformPlanController extends Controller
{
    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly PlanPriceRepository $priceRepository,
        private readonly CreatePlanAction $createPlanAction,
        private readonly UpdatePlanAction $updatePlanAction,
        private readonly ArchivePlanAction $archivePlanAction,
        private readonly DeletePlanAction $deletePlanAction,
        private readonly CreatePlanPriceAction $createPlanPriceAction,
        private readonly MigrateSubscribersToNewPlanAction $migrateSubscribersAction,
    ) {}

    /**
     * List all plans.
     * 
     * GET /v1/platform/billing/plans
     * Query params: include_superseded, is_active, is_public, tier, search
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'include_superseded' => $request->boolean('include_superseded', false),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        if ($request->has('is_public')) {
            $filters['is_public'] = $request->boolean('is_public');
        }

        if ($request->has('tier')) {
            $filters['tier'] = $request->input('tier');
        }

        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }

        $plans = $this->planRepository->listAll($filters);

        return response()->json([
            'data' => $plans,
            'meta' => [
                'total' => $plans->count(),
                'filters' => $filters,
            ],
        ]);
    }

    /**
     * Show a single plan with all relationships.
     * 
     * GET /v1/platform/billing/plans/{plan}
     */
    public function show(int|string $plan): JsonResponse
    {
        $planId = is_string($plan) ? (int) $plan : $plan;
        $plan = $this->planRepository->findWithRelations($planId);

        if (!$plan) {
            return response()->json([
                'error' => 'Plan not found',
            ], 404);
        }

        // Include usage metadata
        $inUse = $this->planRepository->isInUse($plan->id);
        $hasActiveSubscribers = $this->planRepository->hasActiveSubscribers($plan->id);

        return response()->json([
            'data' => $plan,
            'meta' => [
                'in_use' => $inUse,
                'has_active_subscribers' => $hasActiveSubscribers,
                'is_superseded' => $plan->isSuperseded(),
                'is_current' => $plan->isCurrent(),
            ],
        ]);
    }

    /**
     * Create a new plan.
     * 
     * POST /v1/platform/billing/plans
     */
    public function store(StorePlanRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Transform features array
        $features = collect($validated['features'])->map(function ($feature) {
            return [
                'featureKey' => $feature['feature_key'],
                'valueType' => $feature['value_type'],
                'limitValue' => $feature['limit_value'] ?? null,
                'booleanValue' => $feature['boolean_value'] ?? null,
            ];
        })->toArray();

        // Transform prices array
        $prices = collect($validated['prices'])->map(function ($price) {
            return [
                'billingCycle' => $price['billing_cycle'],
                'currency' => $price['currency'],
                'amountCents' => $price['amount_cents'],
                'provider' => $price['provider'] ?? 'stripe',
            ];
        })->toArray();

        $dto = new CreatePlanDTO(
            code: $validated['code'],
            name: $validated['name'],
            description: $validated['description'] ?? null,
            tier: $validated['tier'],
            tierRank: $validated['tier_rank'],
            isPublic: $validated['is_public'],
            isActive: $validated['is_active'],
            trialDays: $validated['trial_days'],
            sortOrder: $validated['sort_order'],
            metadata: $validated['metadata'] ?? null,
            features: $features,
            prices: $prices,
        );

        try {
            $plan = $this->createPlanAction->execute($dto);

            return response()->json([
                'data' => $plan,
                'message' => 'Plan created successfully',
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a plan.
     * May return a new plan ID if versioning occurred.
     * 
     * PUT /v1/platform/billing/plans/{plan}
     */
    public function update(UpdatePlanRequest $request, int|string $plan): JsonResponse
    {
        $planId = is_string($plan) ? (int) $plan : $plan;
        $validated = $request->validated();

        // Transform features if provided
        $features = null;
        if (isset($validated['features'])) {
            $features = collect($validated['features'])->map(function ($feature) {
                return [
                    'featureKey' => $feature['feature_key'],
                    'valueType' => $feature['value_type'],
                    'limitValue' => $feature['limit_value'] ?? null,
                    'booleanValue' => $feature['boolean_value'] ?? null,
                ];
            })->toArray();
        }

        // Transform prices if provided
        $prices = null;
        if (isset($validated['prices'])) {
            $prices = collect($validated['prices'])->map(function ($price) {
                return [
                    'billingCycle' => $price['billing_cycle'],
                    'currency' => $price['currency'],
                    'amountCents' => $price['amount_cents'],
                    'provider' => $price['provider'] ?? 'stripe',
                ];
            })->toArray();
        }

        $dto = new UpdatePlanDTO(
            planId: $planId,
            code: $validated['code'] ?? null,
            name: $validated['name'] ?? null,
            description: $validated['description'] ?? null,
            tier: $validated['tier'] ?? null,
            tierRank: $validated['tier_rank'] ?? null,
            isPublic: $validated['is_public'] ?? null,
            isActive: $validated['is_active'] ?? null,
            trialDays: $validated['trial_days'] ?? null,
            sortOrder: $validated['sort_order'] ?? null,
            metadata: $validated['metadata'] ?? null,
            features: $features,
            prices: $prices,
        );

        try {
            $updatedPlan = $this->updatePlanAction->execute($dto);

            $wasVersioned = $updatedPlan->id !== $planId;

            return response()->json([
                'data' => $updatedPlan,
                'message' => $wasVersioned 
                    ? 'Plan updated (new version created due to breaking changes)'
                    : 'Plan updated successfully',
                'meta' => [
                    'versioned' => $wasVersioned,
                    'original_plan_id' => $planId,
                    'new_plan_id' => $updatedPlan->id,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Archive a plan (safe soft-retire).
     * 
     * PATCH /v1/platform/billing/plans/{plan}/archive
     */
    public function archive(int|string $plan): JsonResponse
    {
        $planId = is_string($plan) ? (int) $plan : $plan;
        
        try {
            $archivedPlan = $this->archivePlanAction->execute($planId);

            return response()->json([
                'data' => $archivedPlan,
                'message' => 'Plan archived successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a plan (only if never used).
     * 
     * DELETE /v1/platform/billing/plans/{plan}
     */
    public function destroy(int|string $plan): JsonResponse
    {
        $planId = is_string($plan) ? (int) $plan : $plan;
        
        try {
            $this->deletePlanAction->execute($planId);

            return response()->json([
                'message' => 'Plan deleted successfully',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create a new price for a plan.
     * 
     * POST /v1/platform/billing/plans/{plan}/prices
     */
    public function storePrice(StorePlanPriceRequest $request, int|string $plan): JsonResponse
    {
        $planId = is_string($plan) ? (int) $plan : $plan;
        $validated = $request->validated();

        $dto = new CreatePlanPriceDTO(
            planId: $planId,
            billingCycle: $validated['billing_cycle'],
            currency: $validated['currency'],
            amountCents: $validated['amount_cents'],
            provider: $validated['provider'] ?? 'stripe',
        );

        try {
            $price = $this->createPlanPriceAction->execute($dto);

            return response()->json([
                'data' => $price,
                'message' => 'Price created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Archive a price.
     * 
     * PATCH /v1/platform/billing/plans/{plan}/prices/{price}/archive
     */
    public function archivePrice(int|string $plan, int|string $price): JsonResponse
    {
        $planId = is_string($plan) ? (int) $plan : $plan;
        $priceId = is_string($price) ? (int) $price : $price;
        
        try {
            $priceModel = $this->priceRepository->findByIdOrFail($priceId);

            if ($priceModel->plan_id !== $planId) {
                return response()->json([
                    'error' => 'Price does not belong to this plan',
                ], 422);
            }

            $this->priceRepository->deactivate($priceId);

            return response()->json([
                'message' => 'Price archived successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Migrate subscribers from one plan to another.
     * 
     * POST /v1/platform/billing/plans/migrate-subscribers
     */
    public function migrateSubscribers(MigrateSubscribersRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new MigrateSubscribersDTO(
            fromPlanId: $validated['from_plan_id'],
            toPlanId: $validated['to_plan_id'],
            billingAccountIds: $validated['billing_account_ids'],
            grandfatherExisting: $validated['grandfather_existing'] ?? false,
            dryRun: $validated['dry_run'] ?? false,
        );

        try {
            $result = $this->migrateSubscribersAction->execute($dto);

            return response()->json([
                'data' => $result,
                'message' => $result['dry_run'] 
                    ? 'Migration analysis completed' 
                    : 'Subscribers migrated successfully',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
