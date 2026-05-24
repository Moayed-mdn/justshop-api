<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Enums\Auth\ActorContextEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Exceptions\Domain\InvalidStoreLifecycleTransitionException;
use App\Models\Store;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StoreLifecycleService
 *
 * Single source of truth for all store status transitions.
 *
 * Rules:
 * - All transitions are atomic (DB transaction).
 * - All transitions are validated against StoreStatusEnum::allowedTransitions().
 * - Idempotent: transitioning to the current status is a no-op.
 * - is_active is kept in sync with status for backwards compatibility.
 * - All transitions are audited with actor context.
 *
 * Architecture rules:
 * - This service is called from Actions only.
 * - Actions are called from Controllers only.
 * - Controllers own authorization via Policies.
 * - This service MUST NOT perform authorization checks.
 */
class StoreLifecycleService
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Transition a store to a new lifecycle status.
     *
     * @param Store $store The store to transition.
     * @param StoreStatusEnum $target The target status.
     * @param int $actorId The ID of the actor performing the transition.
     * @param ActorContextEnum $actorType The type of actor performing the transition.
     * @param string|null $reason Optional reason for the transition (required for suspend/archive).
     *
     * @throws InvalidStoreLifecycleTransitionException if the transition is not allowed.
     */
    public function transition(
        Store $store,
        StoreStatusEnum $target,
        int $actorId,
        ActorContextEnum $actorType,
        ?string $reason = null,
    ): void {
        $current = $store->status;

        // Idempotent: already at target — no-op.
        if ($current === $target) {
            Log::info('store.lifecycle.transition.noop', [
                'store_id' => $store->id,
                'status'   => $target->value,
            ]);
            return;
        }

        // Terminal state guard.
        if ($current === StoreStatusEnum::DELETED_PENDING) {
            throw new InvalidStoreLifecycleTransitionException(
                "Store [{$store->id}] is in terminal state [{$current->value}] and cannot be transitioned."
            );
        }

        // Validate the transition is allowed by the FSM.
        if (!$current->canTransitionTo($target)) {
            throw new InvalidStoreLifecycleTransitionException(
                "Invalid store lifecycle transition from [{$current->value}] to [{$target->value}] for store [{$store->id}]."
            );
        }

        DB::transaction(function () use ($store, $current, $target, $actorId, $actorType, $reason): void {
            $store->update([
                'status'                        => $target,
                // Keep is_active in sync for backwards compatibility with code
                // that still reads is_active directly (e.g. legacy queries).
                'is_active'                     => $target->isOperational(),
                'status_changed_at'             => now(),
                'status_changed_by_actor_type'  => $actorType->value,
                'status_changed_by_actor_id'    => $actorId,
            ]);

            $this->auditLogger->record(
                event: 'store.lifecycle.transitioned',
                metadata: [
                    'store_id'   => $store->id,
                    'from'       => $current->value,
                    'to'         => $target->value,
                    'actor_id'   => $actorId,
                    'actor_type' => $actorType->value,
                    'reason'     => $reason,
                ],
            );
        });

        Log::info('store.lifecycle.transitioned', [
            'store_id'   => $store->id,
            'from'       => $current->value,
            'to'         => $target->value,
            'actor_type' => $actorType->value,
        ]);
    }

    /**
     * Suspend a store. Typically triggered by billing failure or policy violation.
     * Shorthand for transition(store, SUSPENDED, ...).
     */
    public function suspend(Store $store, int $actorId, ActorContextEnum $actorType, string $reason): void
    {
        $this->transition($store, StoreStatusEnum::SUSPENDED, $actorId, $actorType, $reason);
    }

    /**
     * Reactivate a suspended store.
     * Shorthand for transition(store, ACTIVE, ...).
     */
    public function reactivate(Store $store, int $actorId, ActorContextEnum $actorType, string $reason): void
    {
        $this->transition($store, StoreStatusEnum::ACTIVE, $actorId, $actorType, $reason);
    }

    /**
     * Archive a store (voluntary close by owner or admin).
     * Shorthand for transition(store, ARCHIVED, ...).
     */
    public function archive(Store $store, int $actorId, ActorContextEnum $actorType, string $reason): void
    {
        $this->transition($store, StoreStatusEnum::ARCHIVED, $actorId, $actorType, $reason);
    }

    /**
     * Mark a store for deletion (after grace period).
     * Shorthand for transition(store, DELETED_PENDING, ...).
     */
    public function markForDeletion(Store $store, int $actorId, ActorContextEnum $actorType, string $reason): void
    {
        $this->transition($store, StoreStatusEnum::DELETED_PENDING, $actorId, $actorType, $reason);
    }
}
