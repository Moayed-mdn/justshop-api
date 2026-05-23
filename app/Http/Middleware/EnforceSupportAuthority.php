<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Auth\UnauthorizedPlatformAccessException;
use App\Services\Platform\PlatformAuthorityResolver;
use App\Services\Platform\PlatformTelemetry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce Support Authority Middleware
 * 
 * Wave 6: Explicit support authority enforcement.
 * Support routes are a SUBSET of platform authority.
 * Support actors have LIMITED platform access.
 */
class EnforceSupportAuthority
{
    public function __construct(
        private readonly PlatformAuthorityResolver $authorityResolver,
        private readonly PlatformTelemetry $telemetry,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if ($user === null) {
            $this->telemetry->logPlatformAccessDenied($request, $user ?? new \App\Models\User(), 'unauthenticated');
            throw new UnauthorizedPlatformAccessException('Support access requires authentication.');
        }

        if (!$this->authorityResolver->canAccessSupportRoutes($user)) {
            $this->telemetry->logPlatformAccessDenied($request, $user, 'not_support_actor');
            throw new UnauthorizedPlatformAccessException('Support access requires support actor authority.');
        }

        $this->telemetry->logSupportRouteAccess($request, $user, $request->path());

        return $next($request);
    }
}
