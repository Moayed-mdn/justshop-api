<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\GuardShadowAnalyzer;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\SessionGuardTelemetry;
use App\Services\Auth\SessionOwnershipManager;
use App\Services\Auth\SessionOwnershipResolver;
use App\Services\Auth\TransitionalGuardResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutUserAction
{
    public function __construct(
        private readonly IdentityContextResolver $identityContextResolver,
        private readonly SessionOwnershipResolver $sessionOwnershipResolver,
        private readonly GuardShadowAnalyzer $guardShadowAnalyzer,
        private readonly TransitionalGuardResolver $guardResolver,
        private readonly SessionGuardTelemetry $sessionGuardTelemetry,
        private readonly SessionOwnershipManager $sessionOwnershipManager,
    ) {}

    public function execute(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->user();
        $identityContext = $user ? $this->identityContextResolver->resolve($user) : null;
        $ownership = $this->sessionOwnershipResolver->resolve($request, $identityContext);
        $shadow = $this->guardShadowAnalyzer->analyze($ownership);
        $guardResolution = $this->guardResolver->resolve($ownership);

        $this->sessionGuardTelemetry->logLogoutOwnership($request, $ownership, $shadow);

        // Wave 5: Guard-aware logout
        $guard = config('features.auth.guard_split.enabled.default') ? $guardResolution->guard : 'web';
        Auth::guard($guard)->logout();

        $this->sessionOwnershipManager->invalidate($request);
    }
}
