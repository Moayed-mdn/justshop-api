<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Session\GuardResolutionResult;
use App\DTOs\Auth\Session\SessionOwnershipContext;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Support\Facades\Log;

class TransitionalGuardResolver
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    public function resolve(SessionOwnershipContext $context): GuardResolutionResult
    {
        $intendedGuard = $this->determineIntendedGuard($context);
        
        // Step 3 Hardening: All routes except SHARED_TRANSITIONAL MUST use explicit guards.
        $isFallback = $intendedGuard === 'web' && $context->routeDomain !== \App\Enums\Auth\RouteDomainEnum::SHARED_TRANSITIONAL;

        if ($isFallback) {
            Log::error('auth.guard.illegal_fallback_detected', [
                'intended_guard' => $intendedGuard,
                'auth_domain' => $context->authDomain,
                'route_domain' => $context->routeDomain,
                'session_id' => $context->sessionId,
            ]);
        }

        $result = new GuardResolutionResult(
            guard: $intendedGuard,
            authDomain: $context->authDomain,
            isFallback: $isFallback,
            telemetry: [
                'intended_guard_future' => $context->intendedGuardFuture,
                'session_origin' => $context->sessionOrigin,
                'route_domain' => $context->routeDomain,
                'enforcement_active' => true,
            ]
        );

        $this->logResolution($result, $context);

        return $result;
    }

    private function determineIntendedGuard(SessionOwnershipContext $context): string
    {
        // For Wave 4 Phase 2, we are in "additive infrastructure" mode.
        // We resolve the intended guard but don't necessarily enforce it yet if we are in fallback mode.
        
        return match ($context->authDomain) {
            'customer' => 'customer',
            'merchant', 'platform' => 'merchant',
            default => 'web',
        };
    }

    private function logResolution(GuardResolutionResult $result, SessionOwnershipContext $context): void
    {
        Log::info('auth.guard.transitional_resolution', [
            'resolved_guard' => $result->guard,
            'auth_domain' => $result->authDomain,
            'is_fallback' => $result->isFallback,
            'session_id' => $context->sessionId,
            'actor_id' => $context->actorId,
            'route_domain' => $context->routeDomain,
            ...$result->telemetry,
        ]);
    }
}
