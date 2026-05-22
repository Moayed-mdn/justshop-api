<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Identity\IdentityContext;
use App\DTOs\Auth\Identity\RouteDomainContext;
use Illuminate\Http\Request;

class FrontendSessionMetadataResolver
{
    public function __construct(
        private readonly SessionOwnershipResolver $sessionOwnershipResolver,
        private readonly GuardShadowAnalyzer $guardShadowAnalyzer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(
        Request $request,
        ?IdentityContext $identityContext = null,
        ?RouteDomainContext $routeDomainContext = null,
    ): array {
        $ownership = $this->sessionOwnershipResolver->resolve($request, $identityContext, $routeDomainContext);
        $shadow = $this->guardShadowAnalyzer->analyze($ownership);

        return [
            'auth_domain' => $ownership->authDomain,
            'actor_type' => $ownership->actorType,
            'route_domain' => $ownership->routeDomain,
            'onboarding_applicable' => $ownership->onboardingApplicable,
            'future_guard_hint' => $shadow->futureGuardHint,
        ];
    }
}
