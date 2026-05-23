<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Auth\PlatformAuthorityDomainEnum;
use App\Exceptions\Auth\UnauthorizedPlatformAccessException;
use App\Services\Platform\PlatformAuthorityResolver;
use App\Services\Platform\PlatformTelemetry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce Platform Authority Middleware
 * 
 * Wave 6: Explicit platform authority enforcement.
 * Platform routes MUST NOT inherit merchant authority implicitly.
 * Platform routes MUST NOT reuse merchant policies blindly.
 * Platform routes MUST NOT share ownership assumptions.
 */
class EnforcePlatformAuthority
{
    public function __construct(
        private readonly PlatformAuthorityResolver $authorityResolver,
        private readonly PlatformTelemetry $telemetry,
    ) {}

    /**
     * @param string|null $requiredAuthority Comma-separated list of allowed platform authority domains
     */
    public function handle(
        Request $request,
        Closure $next,
        ?string $requiredAuthority = null,
    ): Response {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if ($user === null) {
            $this->telemetry->logPlatformAccessDenied($request, $user ?? new \App\Models\User(), 'unauthenticated');
            throw new UnauthorizedPlatformAccessException('Platform access requires authentication.');
        }

        $platformAuthority = $this->authorityResolver->resolve($user);

        if ($platformAuthority === null) {
            $this->telemetry->logPlatformAccessDenied($request, $user, 'not_platform_actor');
            throw new UnauthorizedPlatformAccessException('Platform access requires platform actor authority.');
        }

        // If specific authority is required, validate it
        if ($requiredAuthority !== null) {
            $allowedAuthorities = array_map('trim', explode(',', $requiredAuthority));
            $allowedAuthorityEnums = array_map(
                fn (string $auth) => PlatformAuthorityDomainEnum::from($auth),
                $allowedAuthorities
            );

            if (!in_array($platformAuthority, $allowedAuthorityEnums, true)) {
                $this->telemetry->logPlatformAccessDenied($request, $user, 'insufficient_platform_authority');
                throw new UnauthorizedPlatformAccessException('Insufficient platform authority for this route.');
            }
        }

        $this->telemetry->logPlatformRouteAccess(
            $request,
            $user,
            $platformAuthority,
            $request->path()
        );

        return $next($request);
    }
}
