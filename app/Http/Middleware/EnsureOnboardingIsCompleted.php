<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Auth\OnboardingStepEnum;
use App\Exceptions\Domain\OnboardingIncompleteException;
use App\Services\Auth\IdentityTelemetry;
use App\Services\Auth\OnboardingApplicabilityResolver;
use App\Support\Security\SecurityEventLoggerInterface;
use App\Support\Security\SecurityEventType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsCompleted
{
    public function __construct(
        private readonly SecurityEventLoggerInterface $securityEventLogger,
        private readonly OnboardingApplicabilityResolver $onboardingApplicabilityResolver,
        private readonly IdentityTelemetry $identityTelemetry,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $applicability = $this->onboardingApplicabilityResolver->resolve($user);
        $this->identityTelemetry->logOnboardingEvaluated($request, $applicability, $user->onboarding_step?->value);

        if (!$applicability->applies) {
            $this->identityTelemetry->logOnboardingBypassed($request, $applicability);

            return $next($request);
        }

        if ($user->onboarding_step !== OnboardingStepEnum::COMPLETED) {
            $this->securityEventLogger->record(SecurityEventType::AUTH_ONBOARDING_DENIED, [
                'route' => $request->path(),
                'required_onboarding_step' => OnboardingStepEnum::COMPLETED->value,
                'current_onboarding_step' => $user->onboarding_step?->value,
                'actor_type' => $applicability->identityContext->actorType->value,
                'auth_domain' => $applicability->identityContext->authDomain->value,
            ], 'notice');

            throw new OnboardingIncompleteException(__('auth.onboarding_incomplete'));
        }

        return $next($request);
    }
}
