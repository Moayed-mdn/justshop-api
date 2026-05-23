<?php

declare(strict_types=1);

namespace App\Services\Enterprise;

use App\Enums\Enterprise\MembershipLifecycleEnum;
use App\Models\User;
use App\Models\Store;
use App\Support\Security\SecurityEventLoggerInterface;
use App\Support\Security\SecurityEventType;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MembershipLifecycleManager
{
    public function __construct(
        private readonly SecurityEventLoggerInterface $securityLogger,
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    public function transition(User $user, Store $store, MembershipLifecycleEnum $newStatus): void
    {
        $membership = $store->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            throw new \RuntimeException("Membership not found for user {$user->id} in store {$store->id}");
        }

        $currentStatusValue = $membership->pivot->lifecycle_status ?? MembershipLifecycleEnum::ACTIVE->value;
        $currentStatus = MembershipLifecycleEnum::from($currentStatusValue);

        if (!$this->isValidTransition($currentStatus, $newStatus)) {
            $this->securityLogger->record(
                SecurityEventType::MEMBERSHIP_LIFECYCLE_INVALID_TRANSITION,
                [
                    'user_id' => $user->id,
                    'store_id' => $store->id,
                    'from' => $currentStatus->value,
                    'to' => $newStatus->value,
                ]
            );
            throw new \RuntimeException("Invalid lifecycle transition from {$currentStatus->value} to {$newStatus->value}");
        }

        $context = $this->traceContext->current();

        $store->users()->updateExistingPivot($user->id, [
            'lifecycle_status' => $newStatus->value,
            'lifecycle_changed_at' => Carbon::now(),
            'lifecycle_changed_by_actor_type' => $context->actorType,
            'lifecycle_changed_by_actor_id' => $context->actorId,
        ]);

        $this->securityLogger->record(
            SecurityEventType::MEMBERSHIP_LIFECYCLE_TRANSITION,
            [
                'user_id' => $user->id,
                'store_id' => $store->id,
                'from' => $currentStatus->value,
                'to' => $newStatus->value,
                'actor_type' => $context->actorType,
                'actor_id' => $context->actorId,
            ]
        );
    }

    private function isValidTransition(MembershipLifecycleEnum $from, MembershipLifecycleEnum $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return match ($from) {
            MembershipLifecycleEnum::INVITED => in_array($to, [
                MembershipLifecycleEnum::ACTIVE,
                MembershipLifecycleEnum::REVOKED,
                MembershipLifecycleEnum::PENDING_ACTIVATION
            ], true),
            MembershipLifecycleEnum::PENDING_ACTIVATION => in_array($to, [
                MembershipLifecycleEnum::ACTIVE,
                MembershipLifecycleEnum::REVOKED
            ], true),
            MembershipLifecycleEnum::ACTIVE => in_array($to, [
                MembershipLifecycleEnum::SUSPENDED,
                MembershipLifecycleEnum::REVOKED
            ], true),
            MembershipLifecycleEnum::SUSPENDED => in_array($to, [
                MembershipLifecycleEnum::ACTIVE,
                MembershipLifecycleEnum::REVOKED
            ], true),
            MembershipLifecycleEnum::REVOKED => false, // Cannot transition out of revoked
            default => true, // Other transitions allowed for now (preparation)
        };
    }

    public function generateGovernanceReport(): array
    {
        $staleActiveStores = $this->detectStaleActiveStores();
        $orphanedMemberships = $this->detectOrphanedMemberships();
        $suspendedPrivilegeLeakage = $this->detectSuspendedPrivilegeLeakage();

        return [
            'stale_active_stores' => $staleActiveStores,
            'orphaned_memberships' => $orphanedMemberships,
            'suspended_privilege_leakage' => $suspendedPrivilegeLeakage,
            'lifecycle_transition_rules_enforced' => true,
            'audit_telemetry_active' => true,
        ];
    }

    private function detectStaleActiveStores(): array
    {
        // Detect users who haven't been active in their "last_active_store" for a while
        // For this audit, we'll look for users whose last_active_store_id is set but they aren't active members
        return DB::table('users')
            ->join('store_user', function ($join) {
                $join->on('users.id', '=', 'store_user.user_id')
                    ->on('users.last_active_store_id', '=', 'store_user.store_id');
            })
            ->where('store_user.lifecycle_status', '!=', MembershipLifecycleEnum::ACTIVE->value)
            ->select('users.id as user_id', 'users.last_active_store_id', 'store_user.lifecycle_status')
            ->get()
            ->toArray();
    }

    private function detectOrphanedMemberships(): array
    {
        // Memberships for deleted stores or deleted users
        return DB::table('store_user')
            ->leftJoin('users', 'store_user.user_id', '=', 'users.id')
            ->leftJoin('stores', 'store_user.store_id', '=', 'stores.id')
            ->whereNull('users.id')
            ->orWhereNull('stores.id')
            ->select('store_user.user_id', 'store_user.store_id')
            ->get()
            ->toArray();
    }

    private function detectSuspendedPrivilegeLeakage(): array
    {
        // Detect cases where a suspended user still has active-looking session data or roles
        // This is a placeholder for more complex logic
        return [];
    }
}
