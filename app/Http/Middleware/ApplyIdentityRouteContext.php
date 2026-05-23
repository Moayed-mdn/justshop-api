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
            allowedActorTypes: $this->allowedActorTypes(AuthDomainEnum::from($ownerAuthDomain)),
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

        if ($routeDomainContext->ownerAuthDomain === AuthDomainEnum::CUSTOMER) {
            $this->telemetry->logCustomerRouteAccess($request, $routeDomainContext, $identityContext);
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
    private function allowedActorTypes(AuthDomainEnum $ownerAuthDomain): array
    {
        return match ($ownerAuthDomain) {
            AuthDomainEnum::MERCHANT => [ActorContextEnum::MERCHANT->value, ActorContextEnum::SUPER_ADMIN->value],
            AuthDomainEnum::CUSTOMER => [ActorContextEnum::CUSTOMER->value],
            AuthDomainEnum::PLATFORM => [ActorContextEnum::SUPER_ADMIN->value],
        };
    }

    private function matchesOwnership(
        \App\DTOs\Auth\Identity\IdentityContext $identityContext,
        RouteDomainContext $routeDomainContext,
    ): bool {
        return $identityContext->authDomain === $routeDomainContext->ownerAuthDomain
            && in_array($identityContext->actorType->value, $routeDomainContext->allowedActorTypes, true);
    }
}
