<?php

namespace App\Actions\Subscription;

use App\DTOs\Subscription\ChangePlanDTO;
use App\Enums\Subscription\SubscriptionEventTypeEnum;
use App\Events\Subscription\PlanDowngradeScheduled;
use App\Models\Subscription;
use App\Repositories\Subscription\PlanRepository;
use App\Repositories\Subscription\SubscriptionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DowngradePlanAction
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepo,
        private PlanRepository $planRepo,
    ) {}

    /**
     * Schedule plan downgrade at period end.
     * 
     * Downgrades are NOT immediate to avoid prorated refunds. The downgrade
     * is scheduled and will be applied at the current period end.
     */
    public function execute(ChangePlanDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = $this->subscriptionRepo->findActiveForAccountOrFail(
                $dto->billingAccountId
            );

            $newPlan = $this->planRepo->findByCodeOrFail($dto->planCode);
            $oldPlan = $subscription->plan;

            // Prevent "downgrade" to same or higher tier
            if ($newPlan->tier_value() >= $oldPlan->tier_value()) {
                throw new \DomainException(
                    "Cannot downgrade to same or higher tier. Use upgrade endpoint instead."
                );
            }

            // Schedule the downgrade (do NOT call Stripe yet)
            $subscription->update([
                'pending_plan_id' => $newPlan->id,
                'pending_plan_effective_at' => $subscription->current_period_ends_at,
            ]);

            // Record event
            $subscription->events()->create([
                'event_type' => SubscriptionEventTypeEnum::DOWNGRADE_SCHEDULED->value,
                'from_status' => $subscription->status->value,
                'to_status' => $subscription->status->value,
                'actor_user_id' => $dto->actorUserId,
                'source' => 'merchant',
                'reason' => "Downgrade scheduled from {$oldPlan->code} to {$newPlan->code}",
                'payload' => [
                    'from_plan' => $oldPlan->code,
                    'to_plan' => $newPlan->code,
                    'cycle' => $dto->billingCycle->value,
                    'effective_at' => $subscription->current_period_ends_at->toIso8601String(),
                ],
            ]);

            event(new PlanDowngradeScheduled($subscription, $oldPlan, $newPlan));

            Log::channel('billing')->info('subscription.downgrade_scheduled', [
                'subscription_id' => $subscription->id,
                'from_plan' => $oldPlan->code,
                'to_plan' => $newPlan->code,
                'effective_at' => $subscription->current_period_ends_at->toIso8601String(),
            ]);

            return $subscription->fresh(['plan', 'pendingPlan']);
        });
    }
}
