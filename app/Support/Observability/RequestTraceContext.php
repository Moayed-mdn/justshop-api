<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\DTOs\Auth\Identity\IdentityContext;
use App\DTOs\Auth\Identity\RouteDomainContext;
use App\DTOs\Auth\Identity\SessionBoundaryMetadata;
use App\DTOs\Auth\Session\GuardShadowSummary;
use App\DTOs\Auth\Session\SessionOwnershipContext;

final readonly class RequestTraceContext
{
    public function __construct(
        public string $correlationId,
        public ?int $actorId,
        public ?string $actorType,
        public ?int $membershipId,
        public ?int $storeId,
        public string $apiDomain,
        public string $releaseVersion,
        public ?string $authDomain = null,
        public ?string $operationalContext = null,
        public ?bool $onboardingRequired = null,
        public ?string $routeDomain = null,
        public ?string $routeOwnerAuthDomain = null,
        public ?string $routeEnforcementMode = null,
        public array $routeAllowedActorTypes = [],
        public ?string $sessionId = null,
        public ?string $sessionAuthDomain = null,
        public ?string $sessionActorType = null,
        public ?int $sessionActorId = null,
        public ?string $sessionAuthorityModel = null,
        public ?string $sessionIsolationState = null,
        public ?string $sessionOwnershipKey = null,
        public ?string $sessionOrigin = null,
        public ?string $sessionIntendedGuardFuture = null,
        public ?bool $sessionOnboardingApplicable = null,
        public ?string $guardFutureHint = null,
        public ?bool $guardAmbiguousOwnershipPath = null,
        public ?bool $guardMismatchAnomaly = null,
    ) {}

    public static function initialize(
        string $correlationId,
        string $apiDomain,
        string $releaseVersion,
    ): self {
        return new self(
            correlationId: $correlationId,
            actorId: null,
            actorType: null,
            membershipId: null,
            storeId: null,
            apiDomain: $apiDomain,
            releaseVersion: $releaseVersion,
        );
    }

    public function withActor(?int $actorId, ?string $actorType): self
    {
        return new self(
            correlationId: $this->correlationId,
            actorId: $actorId,
            actorType: $actorType,
            membershipId: $this->membershipId,
            storeId: $this->storeId,
            apiDomain: $this->apiDomain,
            releaseVersion: $this->releaseVersion,
            authDomain: $this->authDomain,
            operationalContext: $this->operationalContext,
            onboardingRequired: $this->onboardingRequired,
            routeDomain: $this->routeDomain,
            routeOwnerAuthDomain: $this->routeOwnerAuthDomain,
            routeEnforcementMode: $this->routeEnforcementMode,
            routeAllowedActorTypes: $this->routeAllowedActorTypes,
            sessionId: $this->sessionId,
            sessionAuthDomain: $this->sessionAuthDomain,
            sessionActorType: $this->sessionActorType,
            sessionActorId: $this->sessionActorId,
            sessionAuthorityModel: $this->sessionAuthorityModel,
            sessionIsolationState: $this->sessionIsolationState,
            sessionOwnershipKey: $this->sessionOwnershipKey,
            sessionOrigin: $this->sessionOrigin,
            sessionIntendedGuardFuture: $this->sessionIntendedGuardFuture,
            sessionOnboardingApplicable: $this->sessionOnboardingApplicable,
            guardFutureHint: $this->guardFutureHint,
            guardAmbiguousOwnershipPath: $this->guardAmbiguousOwnershipPath,
            guardMismatchAnomaly: $this->guardMismatchAnomaly,
        );
    }

    public function withIdentityContext(IdentityContext $identityContext): self
    {
        return new self(
            correlationId: $this->correlationId,
            actorId: $identityContext->actorId,
            actorType: $identityContext->actorType->value,
            membershipId: $this->membershipId,
            storeId: $this->storeId,
            apiDomain: $this->apiDomain,
            releaseVersion: $this->releaseVersion,
            authDomain: $identityContext->authDomain->value,
            operationalContext: $identityContext->operationalContext->value,
            onboardingRequired: $identityContext->onboardingRequired,
            routeDomain: $this->routeDomain,
            routeOwnerAuthDomain: $this->routeOwnerAuthDomain,
            routeEnforcementMode: $this->routeEnforcementMode,
            routeAllowedActorTypes: $this->routeAllowedActorTypes,
            sessionId: $this->sessionId,
            sessionAuthDomain: $this->sessionAuthDomain,
            sessionActorType: $this->sessionActorType,
            sessionActorId: $this->sessionActorId,
            sessionAuthorityModel: $this->sessionAuthorityModel,
            sessionIsolationState: $this->sessionIsolationState,
            sessionOwnershipKey: $this->sessionOwnershipKey,
            sessionOrigin: $this->sessionOrigin,
            sessionIntendedGuardFuture: $this->sessionIntendedGuardFuture,
            sessionOnboardingApplicable: $this->sessionOnboardingApplicable,
            guardFutureHint: $this->guardFutureHint,
            guardAmbiguousOwnershipPath: $this->guardAmbiguousOwnershipPath,
            guardMismatchAnomaly: $this->guardMismatchAnomaly,
        );
    }

    public function withStore(?int $storeId, ?int $membershipId = null): self
    {
        return new self(
            correlationId: $this->correlationId,
            actorId: $this->actorId,
            actorType: $this->actorType,
            membershipId: $membershipId,
            storeId: $storeId,
            apiDomain: $this->apiDomain,
            releaseVersion: $this->releaseVersion,
            authDomain: $this->authDomain,
            operationalContext: $this->operationalContext,
            onboardingRequired: $this->onboardingRequired,
            routeDomain: $this->routeDomain,
            routeOwnerAuthDomain: $this->routeOwnerAuthDomain,
            routeEnforcementMode: $this->routeEnforcementMode,
            routeAllowedActorTypes: $this->routeAllowedActorTypes,
            sessionId: $this->sessionId,
            sessionAuthDomain: $this->sessionAuthDomain,
            sessionActorType: $this->sessionActorType,
            sessionActorId: $this->sessionActorId,
            sessionAuthorityModel: $this->sessionAuthorityModel,
            sessionIsolationState: $this->sessionIsolationState,
            sessionOwnershipKey: $this->sessionOwnershipKey,
            sessionOrigin: $this->sessionOrigin,
            sessionIntendedGuardFuture: $this->sessionIntendedGuardFuture,
            sessionOnboardingApplicable: $this->sessionOnboardingApplicable,
            guardFutureHint: $this->guardFutureHint,
            guardAmbiguousOwnershipPath: $this->guardAmbiguousOwnershipPath,
            guardMismatchAnomaly: $this->guardMismatchAnomaly,
        );
    }

    public function withRouteDomain(RouteDomainContext $routeDomainContext): self
    {
        return new self(
            correlationId: $this->correlationId,
            actorId: $this->actorId,
            actorType: $this->actorType,
            membershipId: $this->membershipId,
            storeId: $this->storeId,
            apiDomain: $this->apiDomain,
            releaseVersion: $this->releaseVersion,
            authDomain: $this->authDomain,
            operationalContext: $this->operationalContext,
            onboardingRequired: $this->onboardingRequired,
            routeDomain: $routeDomainContext->routeDomain->value,
            routeOwnerAuthDomain: $routeDomainContext->ownerAuthDomain->value,
            routeEnforcementMode: $routeDomainContext->enforcementMode->value,
            routeAllowedActorTypes: $routeDomainContext->allowedActorTypes,
            sessionId: $this->sessionId,
            sessionAuthDomain: $this->sessionAuthDomain,
            sessionActorType: $this->sessionActorType,
            sessionActorId: $this->sessionActorId,
            sessionAuthorityModel: $this->sessionAuthorityModel,
            sessionIsolationState: $this->sessionIsolationState,
            sessionOwnershipKey: $this->sessionOwnershipKey,
            sessionOrigin: $this->sessionOrigin,
            sessionIntendedGuardFuture: $this->sessionIntendedGuardFuture,
            sessionOnboardingApplicable: $this->sessionOnboardingApplicable,
            guardFutureHint: $this->guardFutureHint,
            guardAmbiguousOwnershipPath: $this->guardAmbiguousOwnershipPath,
            guardMismatchAnomaly: $this->guardMismatchAnomaly,
        );
    }

    public function withSessionBoundary(SessionBoundaryMetadata $sessionBoundary): self
    {
        return new self(
            correlationId: $this->correlationId,
            actorId: $this->actorId,
            actorType: $this->actorType,
            membershipId: $this->membershipId,
            storeId: $this->storeId,
            apiDomain: $this->apiDomain,
            releaseVersion: $this->releaseVersion,
            authDomain: $this->authDomain,
            operationalContext: $this->operationalContext,
            onboardingRequired: $this->onboardingRequired,
            routeDomain: $this->routeDomain,
            routeOwnerAuthDomain: $this->routeOwnerAuthDomain,
            routeEnforcementMode: $this->routeEnforcementMode,
            routeAllowedActorTypes: $this->routeAllowedActorTypes,
            sessionId: $sessionBoundary->sessionId,
            sessionAuthDomain: $sessionBoundary->authDomain?->value,
            sessionActorType: $sessionBoundary->actorType?->value,
            sessionActorId: $sessionBoundary->actorId,
            sessionAuthorityModel: $sessionBoundary->authorityModel,
            sessionIsolationState: $sessionBoundary->isolationState,
            sessionOwnershipKey: $sessionBoundary->ownershipKey,
            sessionOrigin: $this->sessionOrigin,
            sessionIntendedGuardFuture: $this->sessionIntendedGuardFuture,
            sessionOnboardingApplicable: $this->sessionOnboardingApplicable,
            guardFutureHint: $this->guardFutureHint,
            guardAmbiguousOwnershipPath: $this->guardAmbiguousOwnershipPath,
            guardMismatchAnomaly: $this->guardMismatchAnomaly,
        );
    }

    public function withSessionOwnership(SessionOwnershipContext $sessionOwnership): self
    {
        return new self(
            correlationId: $this->correlationId,
            actorId: $this->actorId,
            actorType: $this->actorType,
            membershipId: $this->membershipId,
            storeId: $this->storeId,
            apiDomain: $this->apiDomain,
            releaseVersion: $this->releaseVersion,
            authDomain: $this->authDomain,
            operationalContext: $this->operationalContext,
            onboardingRequired: $this->onboardingRequired,
            routeDomain: $this->routeDomain,
            routeOwnerAuthDomain: $this->routeOwnerAuthDomain,
            routeEnforcementMode: $this->routeEnforcementMode,
            routeAllowedActorTypes: $this->routeAllowedActorTypes,
            sessionId: $this->sessionId,
            sessionAuthDomain: $this->sessionAuthDomain,
            sessionActorType: $this->sessionActorType,
            sessionActorId: $this->sessionActorId,
            sessionAuthorityModel: $this->sessionAuthorityModel,
            sessionIsolationState: $this->sessionIsolationState,
            sessionOwnershipKey: $this->sessionOwnershipKey,
            sessionOrigin: $sessionOwnership->sessionOrigin,
            sessionIntendedGuardFuture: $sessionOwnership->intendedGuardFuture,
            sessionOnboardingApplicable: $sessionOwnership->onboardingApplicable,
            guardFutureHint: $this->guardFutureHint,
            guardAmbiguousOwnershipPath: $this->guardAmbiguousOwnershipPath,
            guardMismatchAnomaly: $this->guardMismatchAnomaly,
        );
    }

    public function withGuardShadow(GuardShadowSummary $guardShadow): self
    {
        return new self(
            correlationId: $this->correlationId,
            actorId: $this->actorId,
            actorType: $this->actorType,
            membershipId: $this->membershipId,
            storeId: $this->storeId,
            apiDomain: $this->apiDomain,
            releaseVersion: $this->releaseVersion,
            authDomain: $this->authDomain,
            operationalContext: $this->operationalContext,
            onboardingRequired: $this->onboardingRequired,
            routeDomain: $this->routeDomain,
            routeOwnerAuthDomain: $this->routeOwnerAuthDomain,
            routeEnforcementMode: $this->routeEnforcementMode,
            routeAllowedActorTypes: $this->routeAllowedActorTypes,
            sessionId: $this->sessionId,
            sessionAuthDomain: $this->sessionAuthDomain,
            sessionActorType: $this->sessionActorType,
            sessionActorId: $this->sessionActorId,
            sessionAuthorityModel: $this->sessionAuthorityModel,
            sessionIsolationState: $this->sessionIsolationState,
            sessionOwnershipKey: $this->sessionOwnershipKey,
            sessionOrigin: $this->sessionOrigin,
            sessionIntendedGuardFuture: $this->sessionIntendedGuardFuture,
            sessionOnboardingApplicable: $this->sessionOnboardingApplicable,
            guardFutureHint: $guardShadow->futureGuardHint,
            guardAmbiguousOwnershipPath: $guardShadow->ambiguousOwnershipPath,
            guardMismatchAnomaly: $guardShadow->guardMismatchAnomaly,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'actor_id' => $this->actorId,
            'actor_type' => $this->actorType,
            'membership_id' => $this->membershipId,
            'store_id' => $this->storeId,
            'api_domain' => $this->apiDomain,
            'release_version' => $this->releaseVersion,
            'auth_domain' => $this->authDomain,
            'operational_context' => $this->operationalContext,
            'onboarding_required' => $this->onboardingRequired,
            'route_domain' => $this->routeDomain,
            'route_owner_auth_domain' => $this->routeOwnerAuthDomain,
            'route_enforcement_mode' => $this->routeEnforcementMode,
            'route_allowed_actor_types' => $this->routeAllowedActorTypes,
            'session_id' => $this->sessionId,
            'session_auth_domain' => $this->sessionAuthDomain,
            'session_actor_type' => $this->sessionActorType,
            'session_actor_id' => $this->sessionActorId,
            'session_authority_model' => $this->sessionAuthorityModel,
            'session_isolation_state' => $this->sessionIsolationState,
            'session_ownership_key' => $this->sessionOwnershipKey,
            'session_origin' => $this->sessionOrigin,
            'session_intended_guard_future' => $this->sessionIntendedGuardFuture,
            'session_onboarding_applicable' => $this->sessionOnboardingApplicable,
            'guard_future_hint' => $this->guardFutureHint,
            'guard_ambiguous_ownership_path' => $this->guardAmbiguousOwnershipPath,
            'guard_mismatch_anomaly' => $this->guardMismatchAnomaly,
        ];
    }
}
