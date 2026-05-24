<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Exceptions\Domain\OnboardingTransitionException;
use App\Models\User;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OnboardingTransitionService
 *
 * Single source of truth for all onboarding state transitions.
 *
 * Rules:
 * - All transitions are atomic (DB transaction).
 * - All transitions are idempotent: transitioning to the current step is a no-op.
 * - All transitions are validated against the enum's allowedTransitions().
 * - All transitions are logged to the audit trail.
 * - COMPLETED is a terminal state — no further transitions are allowed.
 * - Null onboarding_step (customer actors) is never transitioned.
 */
class OnboardingTransitionService
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Transition the user to the target onboarding step.
     *
     * @throws OnboardingTransitionException if the transition is invalid.
     */
    public function transition(User $user, OnboardingStepEnum $target): void
    {
        // Customers have null onboarding_step — never transition them.
        if ($user->onboarding_step === null) {
            Log::warning('onboarding.transition.skipped_null_step', [
                'user_id' => $user->id,
                'target'  => $target->value,
            ]);
            return;
        }

        $current = $user->onboarding_step;

        // Idempotent: already at target — no-op.
        if ($current === $target) {
            return;
        }

        // Terminal state guard.
        if ($current === OnboardingStepEnum::COMPLETED) {
            Log::warning('onboarding.transition.already_completed', [
                'user_id' => $user->id,
                'target'  => $target->value,
            ]);
            return;
        }

        // Validate the transition is allowed.
        if (!$current->canTransitionTo($target)) {
            throw new OnboardingTransitionException(
                "Invalid onboarding transition from [{$current->value}] to [{$target->value}] for user [{$user->id}]."
            );
        }
        DB::transaction(function () use ($user, $current, $target): void {
            $updateData = [
                'onboarding_step' => $target,
                'onboarding_step_changed_at' => now(),
            ];

            if ($target === OnboardingStepEnum::COMPLETED) {
                $updateData['onboarding_completed_at'] = now();
            }

            $user->update($updateData);

            $this->auditLogger->record(
                event: 'onboarding.step_transitioned',
                metadata: [
                    'user_id' => $user->id,
                    'from'    => $current->value,
                    'to'      => $target->value,
                ],
            );
        });
    }

    /**
     * Force-set the onboarding step without transition validation.
     * Use ONLY for recovery/admin operations.
     */
    public function forceSet(User $user, OnboardingStepEnum $target, string $reason): void
    {
        $current = $user->onboarding_step;

        DB::transaction(function () use ($user, $current, $target, $reason): void {
            $updateData = [
                'onboarding_step' => $target,
                'onboarding_step_changed_at' => now(),
            ];

            if ($target === OnboardingStepEnum::COMPLETED) {
                $updateData['onboarding_completed_at'] = now();
            }

            $user->update($updateData);

            $this->auditLogger->record(
                event: 'onboarding.step_force_set',
                metadata: [
                    'user_id' => $user->id,
                    'from'    => $current?->value,
                    'to'      => $target->value,
                    'reason'  => $reason,
                ],
            );
        });
    }
}
