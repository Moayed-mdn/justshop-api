<?php

namespace App\Http\Controllers\Api\Billing;

use App\Actions\Subscription\CancelSubscriptionAction;
use App\Actions\Subscription\DowngradePlanAction;
use App\Actions\Subscription\ResumeSubscriptionAction;
use App\Actions\Subscription\UpgradePlanAction;
use App\DTOs\Subscription\CancelSubscriptionDTO;
use App\DTOs\Subscription\ChangePlanDTO;
use App\Enums\ErrorCode;
use App\Enums\Subscription\BillingCycleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CancelSubscriptionRequest;
use App\Http\Requests\Billing\ChangePlanRequest;
use App\Repositories\Billing\BillingAccountRepository;
use App\Repositories\Subscription\SubscriptionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private BillingAccountRepository $billingAccountRepo,
        private SubscriptionRepository $subscriptionRepo,
        private UpgradePlanAction $upgradePlan,
        private DowngradePlanAction $downgradePlan,
        private CancelSubscriptionAction $cancelSubscription,
        private ResumeSubscriptionAction $resumeSubscription,
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

        $billingAccount = $this->billingAccountRepo->findByOwner($user->id);

        if (!$billingAccount) {
            return $this->success([
                'subscription' => null,
                'has_active_subscription' => false,
                'needs_billing_account' => true,
            ]);
        }

        $subscription = $this->subscriptionRepo->getActiveForAccount($billingAccount->id);

        if (!$subscription) {
            return $this->success([
                'subscription' => null,
                'has_active_subscription' => false,
            ]);
        }

        return $this->success([
            'subscription' => $subscription->load(['plan.prices', 'planPrice', 'pendingPlan']),
            'has_active_subscription' => true,
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

        $billingAccount = $this->billingAccountRepo->findByOwner($user->id);

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

        // Get all store entitlement snapshots for this account
        $snapshots = \App\Models\StoreEntitlementSnapshot::where(
            'billing_account_id',
            $billingAccount->id
        )->with('store')->get();

        $usage = [
            'stores' => [
                'count' => $snapshots->count(),
                'limit' => $snapshots->first()?->features['stores.max'] ?? 0,
            ],
            'products' => [
                'count' => $snapshots->sum(fn($s) => $s->limits['products.count'] ?? 0),
                'limit' => $snapshots->first()?->features['products.max'] ?? 0,
            ],
        ];

        return $this->success(['usage' => $usage]);
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

        $billingAccount = $this->billingAccountRepo->findByOwner($user->id);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        try {
            $subscription = $this->upgradePlan->execute(new ChangePlanDTO(
                billingAccountId: $billingAccount->id,
                planCode: $request->validated('plan_code'),
                billingCycle: BillingCycleEnum::from($request->validated('billing_cycle')),
                storeId: $request->validated('store_id'),
                actorUserId: $user->id,
            ));

            return $this->success(
                data: ['subscription' => $subscription->load('plan')],
                message: 'Plan upgraded successfully'
            );
        } catch (\DomainException $e) {
            return $this->error(
                message: $e->getMessage(),
                errorCode: ErrorCode::BIL_009->value,
                statusCode: 422
            );
        } catch (\Exception $e) {
            \Log::error('Subscription upgrade failed', [
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

        $billingAccount = $this->billingAccountRepo->findByOwner($user->id);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        try {
            $subscription = $this->downgradePlan->execute(new ChangePlanDTO(
                billingAccountId: $billingAccount->id,
                planCode: $request->validated('plan_code'),
                billingCycle: BillingCycleEnum::from($request->validated('billing_cycle')),
                storeId: $request->validated('store_id'),
                actorUserId: $user->id,
            ));

            return $this->success(
                data: ['subscription' => $subscription->load(['plan', 'pendingPlan'])],
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

        $billingAccount = $this->billingAccountRepo->findByOwner($user->id);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

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

        $billingAccount = $this->billingAccountRepo->findByOwner($user->id);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

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
}
