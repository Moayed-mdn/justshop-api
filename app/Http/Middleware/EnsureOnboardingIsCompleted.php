<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\Auth\ActorContextEnum;
use App\Enums\RoleEnum;
use App\Exceptions\Domain\OnboardingIncompleteException;
use App\Support\Security\SecurityEventLoggerInterface;
use App\Support\Security\SecurityEventType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsCompleted
{
    public function __construct(
        private readonly SecurityEventLoggerInterface $securityEventLogger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Super admins bypass onboarding check
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $next($request);
        }

        // Customers bypass onboarding check (onboarding is for merchants)
        if ($user->getActorContext() === ActorContextEnum::CUSTOMER) {
            return $next($request);
        }

        if ($user->onboarding_step !== OnboardingStepEnum::COMPLETED) {
            $this->securityEventLogger->record(SecurityEventType::AUTH_ONBOARDING_DENIED, [
                'route' => $request->path(),
                'required_onboarding_step' => OnboardingStepEnum::COMPLETED->value,
                'current_onboarding_step' => $user->onboarding_step?->value,
            ], 'notice');

            throw new OnboardingIncompleteException(__('auth.onboarding_incomplete'));
        }

        return $next($request);
    }
}
