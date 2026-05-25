<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\DTOs\Auth\Identity\RouteDomainContext;
use App\Enums\Auth\ActorContextEnum;
use App\Enums\Auth\AuthDomainEnum;
use App\Enums\Auth\RouteDomainEnforcementModeEnum;
use App\Enums\Auth\RouteDomainEnum;
use App\Exceptions\Domain\InvalidIdentityDomainAccessException;
use App\Models\User;
use App\Services\Auth\GuardShadowAnalyzer;
use App\Services\Auth\GuardSplitSimulationService;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\IdentityTelemetry;
use App\Services\Auth\SessionBoundaryMetadataResolver;
use App\Services\Auth\SessionGuardTelemetry;
use App\Services\Auth\SessionOwnershipResolver;
use App\Services\Auth\TransitionalGuardResolver;
use App\Support\Observability\RequestTraceContextManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyIdentityRouteContext
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
        private readonly IdentityContextResolver $identityContextResolver,
        private readonly SessionBoundaryMetadataResolver $sessionBoundaryMetadataResolver,
        private readonly SessionOwnershipResolver $sessionOwnershipResolver,
        private readonly GuardShadowAnalyzer $guardShadowAnalyzer,
        private readonly TransitionalGuardResolver $guardResolver,
        private readonly GuardSplitSimulationService $guardSplitSimulation,
        private readonly IdentityTelemetry $telemetry,
        private readonly SessionGuardTelemetry $sessionGuardTelemetry,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $routeDomain,
        string $ownerAuthDomain,
        string $enforcementMode = 'observe',
    ): Response {
        $routeDomainContext = new RouteDomainContext(
            routeDomain: RouteDomainEnum::from($routeDomain),
            ownerAuthDomain: AuthDomainEnum::from($ownerAuthDomain),
            enforcementMode: RouteDomainEnforcementModeEnum::from($enforcementMode),
            allowedActorTypes: $this->allowedActorTypes(AuthDomainEnum::from($ownerAuthDomain), RouteDomainEnum::from($routeDomain)),
        );

        $this->traceContext->enrichRouteDomain($routeDomainContext);

        /** @var User|null $user */
        $user = $request->user();
        $identityContext = $user ? $this->identityContextResolver->resolve($user) : null;

        if ($identityContext !== null) {
            $this->traceContext->enrichIdentityContext($identityContext);
        }

        $sessionBoundary = $this->sessionBoundaryMetadataResolver->resolve($request, $identityContext);
        $this->traceContext->enrichSessionBoundary($sessionBoundary);
        $this->telemetry->logSessionBoundaryAnnotated($request, $sessionBoundary, $identityContext);

        $sessionOwnership = $this->sessionOwnershipResolver->resolve($request, $identityContext, $routeDomainContext);
        $guardShadow = $this->guardShadowAnalyzer->analyze($sessionOwnership);
        $guardResolution = $this->guardResolver->resolve($sessionOwnership);
        $this->guardSplitSimulation->simulate($sessionOwnership);

        $this->traceContext->enrichSessionOwnership($sessionOwnership);
        $this->traceContext->enrichGuardShadow($guardShadow);
        $this->traceContext->enrichGuardResolution($guardResolution);

        $this->sessionGuardTelemetry->logSessionOwnershipResolved($request, $sessionOwnership);
        $this->sessionGuardTelemetry->logGuardShadowResolved($request, $sessionOwnership, $guardShadow);
        $this->sessionGuardTelemetry->logContaminationSignals($request, $sessionOwnership, $guardShadow);

        // Wave 6: Guard Authority Activation + Session Isolation Enforcement (single call)
        // Step 3 Hardening: Enforce guard split and isolation strictly.
        \Illuminate\Support\Facades\Auth::shouldUse($guardResolution->guard);
        $this->enforceSessionOwnership($request, $sessionOwnership, $guardResolution);

        if ($routeDomainContext->ownerAuthDomain === AuthDomainEnum::CUSTOMER) {
            $this->telemetry->logCustomerRouteAccess($request, $routeDomainContext, $identityContext);
        }

        if ($routeDomainContext->ownerAuthDomain === AuthDomainEnum::PLATFORM && $identityContext !== null) {
            $this->telemetry->logPlatformAccess($request, $routeDomainContext, $identityContext);
        }

        if ($identityContext !== null && !$this->matchesOwnership($identityContext, $routeDomainContext)) {
            $this->telemetry->logActorDomainMismatch($request, $routeDomainContext, $identityContext);

            if ($routeDomainContext->ownerAuthDomain === AuthDomainEnum::MERCHANT) {
                $this->telemetry->logMerchantRouteMisuse($request, $routeDomainContext, $identityContext);
            }

            if ($routeDomainContext->enforcementMode === RouteDomainEnforcementModeEnum::ENFORCE) {
                $this->telemetry->logCrossContextDenied($request, $routeDomainContext, $identityContext);

                throw new InvalidIdentityDomainAccessException(__('auth.identity_domain_access_denied'));
            }
        }

        return $next($request);
    }

    /**
     * @return string[]
     */
    private function allowedActorTypes(AuthDomainEnum $ownerAuthDomain, RouteDomainEnum $routeDomain): array
    {
        return match ($ownerAuthDomain) {
            AuthDomainEnum::MERCHANT => $this->allowedMerchantActors($routeDomain),
            AuthDomainEnum::CUSTOMER => [ActorContextEnum::CUSTOMER->value],
            AuthDomainEnum::PLATFORM => [ActorContextEnum::SUPER_ADMIN->value, ActorContextEnum::SUPPORT_AGENT->value],
        };
    }

    /**
     * @return string[]
     */
    private function allowedMerchantActors(RouteDomainEnum $routeDomain): array
    {
        // Rule: Super Admins are allowed to access merchant-facing routes (e.g., /v1/me and store admin)
        // This allows them to manage stores they explicitly own or are members of,
        // while the policy layer (HasStoreMembership) prevents implicit global bypass.
        return [ActorContextEnum::MERCHANT->value, ActorContextEnum::SUPER_ADMIN->value];
    }

    private function matchesOwnership(
        \App\DTOs\Auth\Identity\IdentityContext $identityContext,
        RouteDomainContext $routeDomainContext,
    ): bool {
        // Super Admins are authoritative across both Platform and Merchant domains.
        // This allows them to access merchant-facing routes (e.g., /v1/me) while maintaining a Platform identity.
        if ($identityContext->actorType === ActorContextEnum::SUPER_ADMIN) {
            return in_array($routeDomainContext->ownerAuthDomain, [AuthDomainEnum::PLATFORM, AuthDomainEnum::MERCHANT], true);
        }

        return $identityContext->authDomain === $routeDomainContext->ownerAuthDomain
            && in_array($identityContext->actorType->value, $routeDomainContext->allowedActorTypes, true);
    }

    private function enforceSessionOwnership(
        Request $request,
        \App\DTOs\Auth\Session\SessionOwnershipContext $sessionOwnership,
        \App\DTOs\Auth\Session\GuardResolutionResult $guardResolution,
    ): void {
        // If the route domain is shared transitional, we don't enforce strict ownership yet
        if ($sessionOwnership->routeDomain === RouteDomainEnum::SHARED_TRANSITIONAL) {
            return;
        }

        // Detect contamination: session auth domain does not match route domain
        if ($sessionOwnership->sessionAuthDomain !== null && $sessionOwnership->sessionAuthDomain !== $sessionOwnership->authDomain) {
            $this->sessionGuardTelemetry->logSessionContamination(
                $request,
                $sessionOwnership,
                'domain_mismatch'
            );

            // Step 3 Hardening: Strict Enforcement of session ownership.
            throw new InvalidIdentityDomainAccessException('Session contamination detected: domain mismatch.');
        }

        // Enforce guard authority
        // Step 3 Hardening: Strict Enforcement of guard authority.
        if ($guardResolution->isFallback) {
            throw new InvalidIdentityDomainAccessException('Explicit guard authority required for this domain.');
        }
    }
}
