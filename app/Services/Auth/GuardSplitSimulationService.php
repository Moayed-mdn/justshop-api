<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Session\GuardResolutionResult;
use App\DTOs\Auth\Session\SessionOwnershipContext;
use Illuminate\Support\Facades\Log;

class GuardSplitSimulationService
{
    public function __construct(
        private readonly TransitionalGuardResolver $guardResolver,
    ) {}

    public function simulate(SessionOwnershipContext $context): array
    {
        if (!config('features.auth.guard_split.shadow.default')) {
            return [];
        }

        $intended = $this->guardResolver->resolve($context);
        $current = 'web'; // Legacy assumption

        $isParity = $intended->guard === $current;

        $simulation = [
            'intended_guard' => $intended->guard,
            'current_guard' => $current,
            'is_parity' => $isParity,
            'auth_domain' => $context->authDomain,
            'route_domain' => $context->routeDomain,
        ];

        $this->logParity($simulation, $context);

        return $simulation;
    }

    private function logParity(array $simulation, SessionOwnershipContext $context): void
    {
        Log::info('auth.guard.split_simulation', [
            'intended_guard' => $simulation['intended_guard'],
            'current_guard' => $simulation['current_guard'],
            'is_parity' => $simulation['is_parity'],
            'session_id' => $context->sessionId,
            'actor_id' => $context->actorId,
            'route_domain' => $context->routeDomain,
        ]);

        if (!$simulation['is_parity']) {
            Log::warning('auth.guard.split_mismatch_detected', [
                'intended_guard' => $simulation['intended_guard'],
                'current_guard' => $simulation['current_guard'],
                'session_id' => $context->sessionId,
                'actor_id' => $context->actorId,
                'route_domain' => $context->routeDomain,
            ]);
        }
    }
}
