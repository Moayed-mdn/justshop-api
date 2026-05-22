<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Identity\IdentityContext;
use App\DTOs\Auth\Identity\OnboardingApplicability;
use App\DTOs\Auth\Identity\RouteDomainContext;
use App\DTOs\Auth\Identity\SessionBoundaryMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Support\Observability\RequestTraceContextManager;

class IdentityTelemetry
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    public function logCustomerRouteAccess(Request $request, RouteDomainContext $routeDomainContext, ?IdentityContext $identityContext): void
    {
        Log::info('identity.customer_route.accessed', $this->context($request, [
            ...$routeDomainContext->toArray(),
            'identity_context' => $identityContext?->toArray(),
        ]));
    }

    public function logMerchantRouteMisuse(Request $request, RouteDomainContext $routeDomainContext, IdentityContext $identityContext): void
    {
        Log::warning('identity.merchant_route.misused', $this->context($request, [
            ...$routeDomainContext->toArray(),
            'identity_context' => $identityContext->toArray(),
        ]));
    }

    public function logActorDomainMismatch(Request $request, RouteDomainContext $routeDomainContext, IdentityContext $identityContext): void
    {
        Log::warning('identity.actor_domain.mismatch', $this->context($request, [
            ...$routeDomainContext->toArray(),
            'identity_context' => $identityContext->toArray(),
            'allowed_actor_types' => $routeDomainContext->allowedActorTypes,
            'expected_auth_domain' => $routeDomainContext->ownerAuthDomain->value,
            'actual_auth_domain' => $identityContext->authDomain->value,
        ]));
    }

    public function logCrossContextDenied(Request $request, RouteDomainContext $routeDomainContext, IdentityContext $identityContext): void
    {
        Log::warning('identity.cross_context.denied', $this->context($request, [
            ...$routeDomainContext->toArray(),
            'identity_context' => $identityContext->toArray(),
        ]));
    }

    public function logOnboardingEvaluated(Request $request, OnboardingApplicability $applicability, ?string $currentStep): void
    {
        Log::info('identity.onboarding.evaluated', $this->context($request, [
            'onboarding_applies' => $applicability->applies,
            'onboarding_reason' => $applicability->reason,
            'current_onboarding_step' => $currentStep,
            'identity_context' => $applicability->identityContext->toArray(),
        ]));
    }

    public function logOnboardingBypassed(Request $request, OnboardingApplicability $applicability): void
    {
        Log::info('identity.onboarding.bypassed', $this->context($request, [
            'onboarding_reason' => $applicability->reason,
            'identity_context' => $applicability->identityContext->toArray(),
        ]));
    }

    public function logSessionBoundaryAnnotated(Request $request, SessionBoundaryMetadata $metadata, ?IdentityContext $identityContext): void
    {
        Log::info('identity.session_boundary.annotated', $this->context($request, [
            'session_boundary' => $metadata->toArray(),
            'identity_context' => $identityContext?->toArray(),
        ]));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function context(Request $request, array $context): array
    {
        return [
            ...$this->traceContext->current()->toLogContext(),
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            ...$context,
        ];
    }
}
