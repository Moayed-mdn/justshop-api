<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use App\Support\Audit\AuditLoggerInterface;
use App\Support\Security\SecurityEventLoggerInterface;
use Illuminate\Support\Facades\Log;

class OnboardingRecoveryService
{
    public function __construct(
        private readonly OnboardingTransitionService $onboardingTransitionService,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly SecurityEventLoggerInterface $securityEventLogger,
    ) {}

    public function recover(User $user): User
    {
        $step = $user->onboarding_step;

        if ($step === null || $step === OnboardingStepEnum::PENDING_VERIFICATION || $step === OnboardingStepEnum::COMPLETED) {
            return $user;
        }

        $hasStore = $user->stores()->exists();
        $changedAt = $user->onboarding_step_changed_at ?? $user->created_at ?? now();
        $staleCutoff = now()->subMinutes((int) config('lifecycle.onboarding.stale_store_creation_minutes', 15));

        if ($hasStore && $step !== OnboardingStepEnum::COMPLETED) {
            $this->onboardingTransitionService->forceSet(
                $user,
                OnboardingStepEnum::COMPLETED,
                'store_exists_recovery'
            );

            $this->recordRecovery($user, $step, OnboardingStepEnum::COMPLETED, 'store_exists_recovery');

            return $user->fresh();
        }

        if (
            in_array($step, [OnboardingStepEnum::STORE_CREATION_IN_PROGRESS, OnboardingStepEnum::STORE_CREATED], true)
            && $changedAt->lte($staleCutoff)
        ) {
            $this->onboardingTransitionService->forceSet(
                $user,
                OnboardingStepEnum::CREATE_STORE,
                'stale_store_creation_recovery'
            );

            $this->recordRecovery($user, $step, OnboardingStepEnum::CREATE_STORE, 'stale_store_creation_recovery');

            return $user->fresh();
        }

        return $user;
    }

    private function recordRecovery(User $user, OnboardingStepEnum $from, OnboardingStepEnum $to, string $reason): void
    {
        $metadata = [
            'user_id' => (int) $user->id,
            'from' => $from->value,
            'to' => $to->value,
            'reason' => $reason,
        ];

        $this->auditLogger->record('onboarding.recovered', $metadata);
        $this->securityEventLogger->record('onboarding.recovered', $metadata, 'notice');
        Log::warning('onboarding.recovered', $metadata);
    }
}
