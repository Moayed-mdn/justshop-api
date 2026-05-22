<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Identity\IdentityContext;
use App\DTOs\Auth\Identity\RouteDomainContext;
use App\DTOs\Auth\Session\SessionOwnershipContext;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Http\Request;

class SessionOwnershipResolver
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
    ) {}
    public function resolve(
        Request $request,
        ?IdentityContext $identityContext = null,
        ?RouteDomainContext $routeDomainContext = null,
    ): SessionOwnershipContext {
        $currentTrace = $this->traceContext->current();
        $routeDomain = $routeDomainContext?->routeDomain->value ?? $currentTrace->routeDomain;
        $routeOwnerAuthDomain = $routeDomainContext?->ownerAuthDomain->value ?? $currentTrace->routeOwnerAuthDomain;
        $authDomain = $identityContext?->authDomain->value ?? $routeOwnerAuthDomain ?? $currentTrace->authDomain;
        $actorType = $identityContext?->actorType->value;
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        return new SessionOwnershipContext(
            authDomain: $authDomain,
            actorType: $actorType,
            routeDomain: $routeDomain,
            sessionOrigin: $this->resolveSessionOrigin($request, $identityContext),
            intendedGuardFuture: $this->resolveIntendedGuardFuture($authDomain),
            onboardingApplicable: $identityContext?->onboardingRequired ?? false,
            actorId: $identityContext?->actorId,
            routeOwnerAuthDomain: $routeOwnerAuthDomain,
            sessionId: $sessionId,
        );
    }

    public function resolveForCsrf(Request $request): SessionOwnershipContext
    {
        $referer = (string) ($request->headers->get('referer') ?? $request->headers->get('origin') ?? '');
        $routeDomain = str_contains($referer, '/storefront/account') ? 'customer_account' : 'merchant_users';
        $authDomain = $routeDomain === 'customer_account' ? 'customer' : 'merchant';

        return new SessionOwnershipContext(
            authDomain: $authDomain,
            actorType: null,
            routeDomain: $routeDomain,
            sessionOrigin: $request->hasSession() ? 'guest_shared_session' : 'stateless',
            intendedGuardFuture: $authDomain === 'customer' ? 'customer_guard' : 'merchant_guard',
            onboardingApplicable: false,
            actorId: null,
            routeOwnerAuthDomain: $authDomain,
            sessionId: $request->hasSession() ? $request->session()->getId() : null,
        );
    }

    private function resolveSessionOrigin(Request $request, ?IdentityContext $identityContext): string
    {
        if ($identityContext !== null) {
            return 'authenticated_shared_session';
        }

        if (!$request->hasSession()) {
            return 'stateless';
        }

        return 'guest_shared_session';
    }

    private function resolveIntendedGuardFuture(?string $authDomain): string
    {
        return match ($authDomain) {
            'customer' => 'customer_guard',
            'merchant', 'platform' => 'merchant_guard',
            default => 'shared_guard',
        };
    }
}
