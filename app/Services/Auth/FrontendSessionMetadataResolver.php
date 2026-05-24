<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Identity\IdentityContext;
use App\DTOs\Auth\Identity\RouteDomainContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $sessionRecord = null;

        if ($ownership->sessionId !== null) {
            $sessionRecord = DB::table('sessions')
                ->where('id', $ownership->sessionId)
                ->first(['ip_address', 'user_agent', 'last_activity']);
        }

        return [
            // Preserve the legacy bootstrap session contract for the frontend.
            'id' => $ownership->sessionId,
            'ip_address' => $sessionRecord?->ip_address ?? $request->ip(),
            'user_agent' => $sessionRecord?->user_agent ?? $request->userAgent(),
            'last_active_at' => isset($sessionRecord?->last_activity)
                ? gmdate('Y-m-d\TH:i:s\Z', (int) $sessionRecord->last_activity)
                : now()->toIso8601String(),
            'is_current' => $ownership->sessionId !== null,
            'auth_domain' => $ownership->authDomain,
            'actor_type' => $ownership->actorType,
            'route_domain' => $ownership->routeDomain,
            'onboarding_applicable' => $ownership->onboardingApplicable,
            'future_guard_hint' => $shadow->futureGuardHint,
        ];
    }
}
