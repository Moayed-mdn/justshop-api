<?php

namespace App\Actions\Subscription;

use App\Actions\Billing\CreateBillingAccountAction;
use App\Actions\Billing\EnsureBillingCustomerAction;
use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\DTOs\Billing\CreateBillingAccountDTO;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\DTOs\Subscription\StartTrialDTO;
use App\Enums\Subscription\BillingCycleEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Enums\Subscription\SubscriptionEventTypeEnum;
use App\Events\Subscription\TrialStarted;
use App\Exceptions\Subscription\TrialAlreadyUsedException;
use App\Models\BillingAccount;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionItem;
use App\Models\User;
use App\Repositories\Billing\BillingAccountRepository;
use App\Repositories\Subscription\PlanRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StartTrialAction
{
    public function __construct(
        private BillingAccountRepository $billingAccountRepository,
        private PlanRepository $planRepository,
        private CreateBillingAccountAction $createBillingAccountAction,
        private EnsureBillingCustomerAction $ensureBillingCustomerAction,
        private RecomputeEntitlementsAction $recomputeEntitlementsAction,
    ) {}

    /**
     * Start a trial subscription for a new store.
     *
     * @throws TrialAlreadyUsedException
     */
    public function execute(StartTrialDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $user = User::findOrFail($dto->ownerUserId);

            // Step 1: Get or create billing account
            $billingAccount = $this->billingAccountRepository->findByOwner($dto->ownerUserId);

            if (!$billingAccount) {
                $billingAccount = $this->createBillingAccountAction->execute(
                    new CreateBillingAccountDTO(
                        ownerUserId: $dto->ownerUserId,
                        billingEmail: $user->email,
                    )
                );
            }

            // Step 2: Check trial eligibility (prevent trial gaming)
            if ($billingAccount->trial_used) {
                throw new TrialAlreadyUsedException(
                    'This account has already used its free trial.'
                );
            }

            // Step 3: Get the plan (default to starter)
            $plan = $this->planRepository->findCurrentByCodeOrFail($dto->planCode);

            // Step 4: Ensure billing customer exists (local record only for now)
            $this->ensureBillingCustomerAction->execute($billingAccount);

            // Step 5: Calculate trial dates
            $trialStartsAt = Carbon::now();
            $trialEndsAt = $trialStartsAt->copy()->addDays($plan->trial_days);

            // Step 6: Create subscription with TRIALING status
            $subscription = Subscription::create([
                'billing_account_id' => $billingAccount->id,
                'plan_id' => $plan->id,
                'plan_price_id' => null, // No price during trial
                'status' => SubscriptionStatusEnum::TRIALING->value,
                'billing_cycle' => BillingCycleEnum::MONTHLY->value, // Default to monthly
                'provider' => 'stripe',
                'provider_subscription_id' => null, // Will be set in Phase 3
                'provider_status' => null,
                'trial_starts_at' => $trialStartsAt,
                'trial_ends_at' => $trialEndsAt,
                'current_period_starts_at' => $trialStartsAt,
                'current_period_ends_at' => $trialEndsAt,
            ]);

            // Step 7: Create subscription item (base plan)
            SubscriptionItem::create([
                'subscription_id' => $subscription->id,
                'plan_price_id' => null, // No price during trial
                'provider_item_id' => null,
                'quantity' => 1,
                'item_type' => 'base',
            ]);

            // Step 8: Mark trial as used (prevent future trial gaming)
            $this->billingAccountRepository->markTrialAsUsed($billingAccount);

            // Step 9: Create subscription event
            SubscriptionEvent::create([
                'subscription_id' => $subscription->id,
                'event_type' => SubscriptionEventTypeEnum::TRIAL_STARTED->value,
                'from_status' => null,
                'to_status' => SubscriptionStatusEnum::TRIALING->value,
                'actor_user_id' => $dto->ownerUserId,
                'source' => 'system',
                'reason' => 'store_creation',
                'payload' => [
                    'store_id' => $dto->storeId,
                    'plan_code' => $dto->planCode,
                    'trial_days' => $plan->trial_days,
                ],
            ]);

            // Step 10: Recompute entitlements for the store
            $this->recomputeEntitlementsAction->execute(
                new RecomputeEntitlementsDTO(
                    billingAccountId: $billingAccount->id,
                    storeId: $dto->storeId,
                )
            );

            // Step 11: Fire TrialStarted event
            event(new TrialStarted($subscription, $dto->storeId));

            // Log to billing channel
            Log::channel('billing')->info('trial.started', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $billingAccount->id,
                'store_id' => $dto->storeId,
                'plan_code' => $dto->planCode,
                'trial_ends_at' => $trialEndsAt->toDateTimeString(),
            ]);

            return $subscription->fresh(['plan', 'items']);
        });
    }
}
