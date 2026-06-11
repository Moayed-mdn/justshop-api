<?php

namespace App\Actions\Subscription;

use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Subscription\CancelSubscriptionDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Events\Subscription\SubscriptionCanceled;
use App\Models\Subscription;
use App\Repositories\Subscription\SubscriptionRepository;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelSubscriptionAction
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepo,
        private BillingProviderInterface $billingProvider,
        private SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Cancel subscription (at period end or immediately).
     * 
     * By default, cancels at period end (merchant retains access until then).
     * If $cancelImmediately = true, terminates access immediately.
     */
    public function execute(CancelSubscriptionDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = $this->subscriptionRepo->findActiveForAccountOrFail(
                $dto->billingAccountId
            );

            // Cancel in Stripe
            $this->billingProvider->cancelSubscription(
                subscription: $subscription,
                immediately: $dto->cancelImmediately
            );

            if ($dto->cancelImmediately) {
                // Immediate cancellation → transition to CANCELED status
                $subscription = $this->stateMachine->transition(
                    $subscription,
                    SubscriptionStatusEnum::CANCELED,
                    source: 'merchant',
                    reason: $dto->reason ?? 'immediate_cancellation',
                    actorUserId: $dto->actorUserId,
                );

                $subscription->update([
                    'canceled_at' => now(),
                    'cancel_at_period_end' => false,
                    'provider_synced_at' => now(),
                ]);
            } else {
                // Cancel at period end → stay ACTIVE but flag cancellation
                $subscription->update([
                    'cancel_at_period_end' => true,
                    'canceled_at' => now(),
                    'provider_synced_at' => now(),
                ]);

                // Record event (no status change yet)
                $subscription->events()->create([
                    'event_type' => \App\Enums\Subscription\SubscriptionEventTypeEnum::CANCELED->value,
                    'from_status' => $subscription->status->value,
                    'to_status' => $subscription->status->value,
                    'actor_user_id' => $dto->actorUserId,
                    'source' => 'merchant',
                    'reason' => $dto->reason ?? 'cancel_at_period_end',
                    'payload' => [
                        'cancel_at_period_end' => true,
                        'access_until' => $subscription->current_period_ends_at->toIso8601String(),
                    ],
                ]);
            }

            event(new SubscriptionCanceled($subscription, $dto->cancelImmediately));

            Log::channel('billing')->info('subscription.canceled', [
                'subscription_id' => $subscription->id,
                'immediate' => $dto->cancelImmediately,
                'reason' => $dto->reason,
            ]);

            return $subscription->fresh();
        });
    }
}
