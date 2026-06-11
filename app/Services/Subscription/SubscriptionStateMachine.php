<?php

namespace App\Services\Subscription;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Enums\Subscription\SubscriptionEventTypeEnum;
use App\Exceptions\Subscription\InvalidSubscriptionTransitionException;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionStateMachine
{
    /**
     * Transition a subscription from one status to another.
     *
     * @param Subscription $subscription
     * @param SubscriptionStatusEnum $toStatus
     * @param string $source 'system' | 'merchant' | 'admin' | 'webhook'
     * @param string|null $reason
     * @param int|null $actorUserId
     * @param array $payload Additional context data
     * @return Subscription
     * @throws InvalidSubscriptionTransitionException
     */
    public function transition(
        Subscription $subscription,
        SubscriptionStatusEnum $toStatus,
        string $source = 'system',
        ?string $reason = null,
        ?int $actorUserId = null,
        array $payload = []
    ): Subscription {
        $fromStatus = $subscription->status;

        // Prevent no-op transitions
        if ($fromStatus === $toStatus) {
            Log::channel('billing')->debug('subscription.transition.noop', [
                'subscription_id' => $subscription->id,
                'status' => $toStatus->value,
            ]);

            return $subscription;
        }

        // Validate transition is allowed
        if (!$fromStatus->canTransitionTo($toStatus)) {
            throw new InvalidSubscriptionTransitionException(
                "Cannot transition subscription from {$fromStatus->value} to {$toStatus->value}"
            );
        }

        return DB::transaction(function () use (
            $subscription,
            $fromStatus,
            $toStatus,
            $source,
            $reason,
            $actorUserId,
            $payload
        ) {
            // Update subscription status
            $subscription->status = $toStatus;
            $subscription->save();

            // Derive event type from transition
            $eventType = $this->deriveEventType($fromStatus, $toStatus);

            // Create audit event
            SubscriptionEvent::create([
                'subscription_id' => $subscription->id,
                'event_type' => $eventType->value,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'actor_user_id' => $actorUserId,
                'source' => $source,
                'reason' => $reason,
                'payload' => $payload,
            ]);

            // Log to billing channel
            Log::channel('billing')->info('subscription.transition', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $subscription->billing_account_id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'event_type' => $eventType->value,
                'source' => $source,
                'reason' => $reason,
                'actor_user_id' => $actorUserId,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Derive the appropriate event type from status transition.
     */
    private function deriveEventType(
        SubscriptionStatusEnum $from,
        SubscriptionStatusEnum $to
    ): SubscriptionEventTypeEnum {
        return match (true) {
            $to === SubscriptionStatusEnum::TRIALING => SubscriptionEventTypeEnum::TRIAL_STARTED,
            $to === SubscriptionStatusEnum::ACTIVE && $from === SubscriptionStatusEnum::TRIALING => SubscriptionEventTypeEnum::TRIAL_CONVERTED,
            $to === SubscriptionStatusEnum::ACTIVE && $from === SubscriptionStatusEnum::CANCELED => SubscriptionEventTypeEnum::REACTIVATED,
            $to === SubscriptionStatusEnum::ACTIVE && $from === SubscriptionStatusEnum::PAST_DUE => SubscriptionEventTypeEnum::PAYMENT_RECOVERED,
            $to === SubscriptionStatusEnum::ACTIVE && $from === SubscriptionStatusEnum::GRACE_PERIOD => SubscriptionEventTypeEnum::PAYMENT_RECOVERED,
            $to === SubscriptionStatusEnum::PAST_DUE => SubscriptionEventTypeEnum::PAYMENT_FAILED,
            $to === SubscriptionStatusEnum::GRACE_PERIOD => SubscriptionEventTypeEnum::GRACE_PERIOD_STARTED,
            $to === SubscriptionStatusEnum::CANCELED => SubscriptionEventTypeEnum::CANCELED,
            $to === SubscriptionStatusEnum::PAUSED => SubscriptionEventTypeEnum::PAUSED,
            $to === SubscriptionStatusEnum::EXPIRED && $from === SubscriptionStatusEnum::TRIALING => SubscriptionEventTypeEnum::TRIAL_EXPIRED,
            $to === SubscriptionStatusEnum::EXPIRED => SubscriptionEventTypeEnum::EXPIRED,
            default => SubscriptionEventTypeEnum::STATUS_CHANGED,
        };
    }

    /**
     * Check if a subscription can transition to a given status.
     */
    public function canTransition(
        Subscription $subscription,
        SubscriptionStatusEnum $toStatus
    ): bool {
        return $subscription->status->canTransitionTo($toStatus);
    }

    /**
     * Get allowed transitions for a subscription's current status.
     *
     * @return array<SubscriptionStatusEnum>
     */
    public function getAllowedTransitions(Subscription $subscription): array
    {
        return $subscription->status->allowedTransitions();
    }
}
