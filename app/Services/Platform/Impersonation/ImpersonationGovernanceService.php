<?php

declare(strict_types=1);

namespace App\Services\Platform\Impersonation;

use App\Enums\Auth\ActorContextEnum;
use App\Models\User;
use App\Models\Impersonation;
use App\Support\Security\SecurityEventLoggerInterface;
use App\Support\Security\SecurityEventType;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ImpersonationGovernanceService
{
    public function __construct(
        private readonly ImpersonationLifecycleManager $lifecycleManager,
        private readonly SecurityEventLoggerInterface $securityLogger,
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    public function validateImpersonationRequest(User $initiator, User $target): void
    {
        $initiatorActor = $initiator->getActorContext();
        $targetActor = $target->getActorContext();

        // 1. Prevent nested impersonation
        if ($this->isAlreadyImpersonating($initiator)) {
            $this->securityLogger->record(
                'impersonation.escalation_blocked',
                ['reason' => 'nested_impersonation', 'initiator_id' => $initiator->id]
            );
            throw new \RuntimeException('Nested impersonation is forbidden.');
        }

        // 2. Enforce actor-domain restrictions
        if ($initiatorActor === ActorContextEnum::MERCHANT && $targetActor === ActorContextEnum::CUSTOMER) {
            $this->logDenied($initiator, $target, 'merchant_to_customer_forbidden');
            throw new \RuntimeException('Merchants cannot impersonate customers.');
        }

        if ($initiatorActor === ActorContextEnum::CUSTOMER && $targetActor === ActorContextEnum::MERCHANT) {
            $this->logDenied($initiator, $target, 'customer_to_merchant_forbidden');
            throw new \RuntimeException('Customers cannot impersonate merchants.');
        }

        // 3. Platform/Support only for now
        if (!in_array($initiatorActor, [ActorContextEnum::SUPPORT_AGENT, ActorContextEnum::SUPER_ADMIN, ActorContextEnum::PLATFORM_SYSTEM], true)) {
            $this->logDenied($initiator, $target, 'non_platform_initiator');
            throw new \RuntimeException('Only platform actors can initiate impersonation.');
        }
    }

    public function secureActivate(Request $request, Impersonation $impersonation): void
    {
        // Generate correlation ID for this impersonation session
        $correlationId = (string) Str::uuid();
        
        // Tag session
        $request->session()->put('impersonation_correlation_id', $correlationId);
        $request->session()->put('impersonation_id', $impersonation->id);
        
        $this->lifecycleManager->activate($request, $impersonation);

        $this->securityLogger->record(
            'impersonation.started',
            [
                'impersonation_id' => $impersonation->id,
                'correlation_id' => $correlationId,
                'initiator_id' => $impersonation->initiator_id,
                'target_id' => $impersonation->target_id,
            ]
        );
    }

    public function secureTerminate(Request $request, Impersonation $impersonation, string $reason): void
    {
        $this->lifecycleManager->terminate($request, $impersonation, $reason);

        // Clear session
        $request->session()->forget(['impersonation_correlation_id', 'impersonation_id']);
        
        $this->securityLogger->record(
            'impersonation.ended',
            [
                'impersonation_id' => $impersonation->id,
                'reason' => $reason,
            ]
        );
    }

    private function isAlreadyImpersonating(User $user): bool
    {
        return $this->lifecycleManager->getActive($user) !== null;
    }

    private function logDenied(User $initiator, User $target, string $reason): void
    {
        $this->securityLogger->record(
            'impersonation.denied',
            [
                'initiator_id' => $initiator->id,
                'target_id' => $target->id,
                'reason' => $reason,
            ]
        );
    }

    public function generateAuditReport(): array
    {
        try {
            $total = Impersonation::count();
            $active = Impersonation::where('status', 'active')->count();
            $expired = Impersonation::where('status', 'expired')->count();
            $terminated = Impersonation::where('status', 'terminated')->count();

            $violations = DB::table('security_events')
                ->whereIn('event', ['impersonation.denied', 'impersonation.escalation_blocked'])
                ->count();
        } catch (\Throwable) {
            // Security events might be in logs only
            $total = 0;
            $active = 0;
            $expired = 0;
            $terminated = 0;
            $violations = 0;
        }

        return [
            'total_impersonations' => $total,
            'active_impersonations' => $active,
            'expired_impersonations' => $expired,
            'terminated_impersonations' => $terminated,
            'security_violations_detected' => $violations,
            'governance_status' => 'enforced',
        ];
    }
}
