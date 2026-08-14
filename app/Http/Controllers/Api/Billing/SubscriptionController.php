<?php

namespace App\Http\Controllers\Api\Billing;

use App\Actions\Subscription\CancelSubscriptionAction;
use App\Actions\Subscription\DowngradePlanAction;
use App\Actions\Subscription\MoveToCurrentPlanVersionAction;
use App\Actions\Subscription\ResumeSubscriptionAction;
use App\Actions\Subscription\UpgradePlanAction;
use App\DTOs\Subscription\CancelSubscriptionDTO;
use App\DTOs\Subscription\ChangePlanDTO;
use App\Enums\ErrorCode;
use App\Enums\Subscription\BillingCycleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CancelSubscriptionRequest;
use App\Http\Requests\Billing\ChangePlanRequest;
use App\Models\Store;
use App\Policies\Billing\SubscriptionPolicy;
use App\Repositories\Billing\BillingAccountRepository;
use App\Repositories\Subscription\SubscriptionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private BillingAccountRepository $billingAccountRepo,
        private SubscriptionRepository $subscriptionRepo,
        private UpgradePlanAction $upgradePlan,
        private DowngradePlanAction $downgradePlan,
        private CancelSubscriptionAction $cancelSubscription,
        private ResumeSubscriptionAction $resumeSubscription,
        private MoveToCurrentPlanVersionAction $moveToCurrentVersion,
    ) {}

    /**
     * Get current subscription details.
     * 
     * GET /api/v1/users/billing/subscription
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->success([
                'subscription' => null,
                'has_active_subscription' => false,
                'needs_billing_account' => true,
            ]);
        }

        $this->authorize('view', [SubscriptionPolicy::class, $billingAccount]);

        $subscription = $this->subscriptionRepo->getActiveForAccount($billingAccount->id);

        if (!$subscription) {
            // Check for pending checkout even if no active subscription
            $incompleteSubscription = $this->subscriptionRepo->getLatestIncompleteForAccount($billingAccount->id);

            return $this->success([
                'subscription' => null,
                'has_active_subscription' => false,
                'pending_checkout' => $incompleteSubscription ? [
                    'subscription_id' => $incompleteSubscription->id,
                    'plan_id' => $incompleteSubscription->plan_id,
                    'plan_price_id' => $incompleteSubscription->plan_price_id,
                    'created_at' => $incompleteSubscription->created_at,
                ] : null,
            ]);
        }

        // Check for pending checkout alongside active subscription
        $incompleteSubscription = $this->subscriptionRepo->getLatestIncompleteForAccount($billingAccount->id);

        // Load all required relationships including features
        $subscription->load(['plan.prices', 'plan.features', 'planPrice', 'pendingPlan']);

        // Determine if the current plan is still a "current offering" 
        // (active, public, and not superseded by another plan)
        $planIsCurrentOffering = $subscription->plan->is_active 
            && $subscription->plan->is_public 
            && is_null($subscription->plan->superseded_by_plan_id);

        return $this->success([
            'subscription' => $subscription,
            'has_active_subscription' => true,
            'plan_is_current_offering' => $planIsCurrentOffering,
            'pending_checkout' => $incompleteSubscription ? [
                'subscription_id' => $incompleteSubscription->id,
                'plan_id' => $incompleteSubscription->plan_id,
                'plan_price_id' => $incompleteSubscription->plan_price_id,
                'created_at' => $incompleteSubscription->created_at,
            ] : null,
        ]);
    }

    /**
     * Get subscription usage statistics.
     * 
     * GET /api/v1/users/billing/subscription/usage
     */
    public function usage(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->success([
                'usage' => [
                    'stores' => [
                        'count' => 0,
                        'limit' => 1,
                    ],
                    'products' => [
                        'count' => 0,
                        'limit' => 0,
                    ],
                ],
            ]);
        }

        $this->authorize('viewUsage', [SubscriptionPolicy::class, $billingAccount]);

        return $this->success(['usage' => $this->resolveUsage($billingAccount)]);
    }

    /**
     * Build the current usage/limits payload for a billing account.
     *
     * Bug fix: previously used $snapshots->first() with no ordering, which
     * returned an arbitrary (effectively oldest-inserted) row. For multi-store
     * accounts this could report a stale products limit even right after a
     * successful plan change. Now takes the most-recently-refreshed snapshot.
     *
     * IMPORTANT: pass a FRESH $billingAccount (->fresh()) when calling this
     * right after a mutation (upgrade/downgrade), since stores_max was just
     * updated in the DB by RecomputeEntitlementsAction and the in-memory
     * instance loaded earlier in the request won't reflect that automatically.
     */
    private function resolveUsage(\App\Models\BillingAccount $billingAccount): array
    {
        $snapshots = \App\Models\StoreEntitlementSnapshot::where(
            'billing_account_id',
            $billingAccount->id
        )->get();

        $latestSnapshot = $snapshots->sortByDesc('refreshed_at')->first();

        return [
            'stores' => [
                'count' => $billingAccount->stores_count,
                'limit' => $billingAccount->stores_max,
            ],
            'products' => [
                'count' => $snapshots->sum('products_count'),
                'limit' => $latestSnapshot?->features[\App\Enums\Entitlement\FeatureKeyEnum::PRODUCTS_MAX->value] ?? null,
            ],
        ];
    }

    /**
     * Upgrade plan (immediate, with proration).
     * 
     * POST /api/v1/users/billing/subscription/upgrade
     */
    public function upgrade(ChangePlanRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        $store = Store::findOrFail((int) $request->validated('store_id'));
        $this->authorize('upgrade', [SubscriptionPolicy::class, $billingAccount, $store]);

        try {
            $subscription = $this->upgradePlan->execute(new ChangePlanDTO(
                billingAccountId: $billingAccount->id,
                planCode: $request->validated('plan_code'),
                billingCycle: BillingCycleEnum::from($request->validated('billing_cycle')),
                storeId: $store->id,
                actorUserId: $user->id,
            ));

            return $this->success(
                data: [
                    'subscription' => $subscription->load('plan'),
                    // Bug fix: return fresh usage/limits so the client can update
                    // "Usage & Limits" immediately without a second request.
                    'usage' => $this->resolveUsage($billingAccount->fresh()),
                ],
                message: 'Plan upgraded successfully'
            );
        } catch (\DomainException $e) {
            return $this->error(
                message: $e->getMessage(),
                errorCode: ErrorCode::BIL_009->value,
                statusCode: 422
            );
        } catch (\Exception $e) {
            Log::error('Subscription upgrade failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'billing_account_id' => $billingAccount->id,
                'plan_code' => $request->validated('plan_code'),
            ]);
            
            return $this->error(
                message: 'Failed to upgrade plan: ' . $e->getMessage(),
                errorCode: ErrorCode::BIL_010->value,
                statusCode: 500
            );
        }
    }

    /**
     * Downgrade plan (scheduled at period end).
     * 
     * POST /api/v1/users/billing/subscription/downgrade
     */
    public function downgrade(ChangePlanRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        $store = Store::findOrFail((int) $request->validated('store_id'));
        $this->authorize('downgrade', [SubscriptionPolicy::class, $billingAccount, $store]);

        try {
            $subscription = $this->downgradePlan->execute(new ChangePlanDTO(
                billingAccountId: $billingAccount->id,
                planCode: $request->validated('plan_code'),
                billingCycle: BillingCycleEnum::from($request->validated('billing_cycle')),
                storeId: $store->id,
                actorUserId: $user->id,
            ));

            return $this->success(
                data: [
                    'subscription' => $subscription->load(['plan', 'pendingPlan']),
                    'usage' => $this->resolveUsage($billingAccount->fresh()),
                ],
                message: 'Plan downgrade scheduled for period end'
            );
        } catch (\DomainException $e) {
            return $this->error(
                message: $e->getMessage(),
                errorCode: ErrorCode::BIL_009->value,
                statusCode: 422
            );
        } catch (\Exception $e) {
            Log::error('Subscription downgrade failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'billing_account_id' => $billingAccount->id,
                'plan_code' => $request->validated('plan_code'),
            ]);
            
            return $this->error(
                message: 'Failed to schedule downgrade: ' . $e->getMessage(),
                errorCode: ErrorCode::BIL_010->value,
                statusCode: 500
            );
        }
    }

    /**
     * Cancel subscription.
     * 
     * POST /api/v1/users/billing/subscription/cancel
     */
    public function cancel(CancelSubscriptionRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        $this->authorize('cancel', [SubscriptionPolicy::class, $billingAccount]);

        try {
            $subscription = $this->cancelSubscription->execute(new CancelSubscriptionDTO(
                billingAccountId: $billingAccount->id,
                cancelImmediately: $request->validated('cancel_immediately', false),
                reason: $request->validated('reason'),
                actorUserId: $user->id,
            ));

            $message = $request->validated('cancel_immediately', false)
                ? 'Subscription canceled immediately'
                : 'Subscription will cancel at period end';

            return $this->success(
                data: ['subscription' => $subscription],
                message: $message
            );
        } catch (\DomainException $e) {
            return $this->error(
                message: $e->getMessage(),
                errorCode: ErrorCode::BIL_009->value,
                statusCode: 422
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Failed to cancel subscription',
                errorCode: ErrorCode::BIL_011->value,
                statusCode: 500
            );
        }
    }

    /**
     * Resume a canceled or paused subscription.
     * 
     * POST /api/v1/users/billing/subscription/resume
     */
    public function resume(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        $this->authorize('resume', [SubscriptionPolicy::class, $billingAccount]);

        try {
            $subscription = $this->resumeSubscription->execute(
                billingAccountId: $billingAccount->id,
                actorUserId: $user->id,
            );

            return $this->success(
                data: ['subscription' => $subscription],
                message: 'Subscription resumed successfully'
            );
        } catch (\DomainException $e) {
            return $this->error(
                message: $e->getMessage(),
                errorCode: ErrorCode::BIL_012->value,
                statusCode: 422
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Failed to resume subscription',
                errorCode: ErrorCode::BIL_013->value,
                statusCode: 500
            );
        }
    }

    /**
     * Move subscription to current plan version.
     * 
     * This endpoint allows merchants on superseded plans to move to the current
     * version without tier checking. It follows the superseded_by_plan_id pointer.
     * 
     * POST /api/v1/users/billing/subscription/move-to-current-version
     */
    public function moveToCurrentVersion(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        $this->authorize('moveToCurrentVersion', [SubscriptionPolicy::class, $billingAccount]);

        try {
            $subscription = $this->moveToCurrentVersion->execute(
                billingAccountId: $billingAccount->id,
                actorUserId: $user->id,
            );

            return $this->success(
                data: [
                    'subscription' => $subscription->load('plan'),
                    'usage' => $this->resolveUsage($billingAccount->fresh()),
                ],
                message: 'Successfully moved to current plan version'
            );
        } catch (\DomainException $e) {
            return $this->error(
                message: $e->getMessage(),
                errorCode: ErrorCode::BIL_009->value,
                statusCode: 422
            );
        } catch (\Exception $e) {
            Log::error('Move to current version failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'billing_account_id' => $billingAccount->id,
            ]);
            
            return $this->error(
                message: 'Failed to move to current version: ' . $e->getMessage(),
                errorCode: ErrorCode::BIL_010->value,
                statusCode: 500
            );
        }
    }
}
