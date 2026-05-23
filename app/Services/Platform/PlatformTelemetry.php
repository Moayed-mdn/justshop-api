<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\Auth\PlatformAuthorityDomainEnum;
use App\Models\User;
use App\Services\Telemetry\RequestTraceContextManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Platform Telemetry
 * 
 * Wave 6: Platform-specific telemetry domain.
 * Platform telemetry is INDEPENDENT from merchant/customer telemetry.
 */
class PlatformTelemetry
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    public function logPlatformRouteAccess(
        Request $request,
        User $user,
        PlatformAuthorityDomainEnum $authority,
        string $route,
    ): void {
        Log::info('platform.route.accessed', $this->context($request, [
            'authority_domain' => $authority->value,
            'actor_type' => $user->getActorContext()->value,
            'actor_id' => $user->id,
            'route' => $route,
        ]));
    }

    public function logSupportRouteAccess(
        Request $request,
        User $user,
        string $route,
    ): void {
        Log::info('platform.support.route_accessed', $this->context($request, [
            'actor_type' => $user->getActorContext()->value,
            'actor_id' => $user->id,
            'route' => $route,
        ]));
    }

    public function logPlatformAccessDenied(
        Request $request,
        User $user,
        string $reason,
    ): void {
        Log::warning('platform.access.denied', $this->context($request, [
            'actor_type' => $user->getActorContext()->value,
            'actor_id' => $user->id,
            'reason' => $reason,
        ]));
    }

    public function logPlatformOverride(
        Request $request,
        User $user,
        string $action,
        array $metadata,
    ): void {
        Log::warning('platform.override.executed', $this->context($request, [
            'actor_type' => $user->getActorContext()->value,
            'actor_id' => $user->id,
            'action' => $action,
            'metadata' => $metadata,
        ]));
    }

    public function logSupportEscalation(
        Request $request,
        User $supportAgent,
        string $escalationType,
        array $metadata,
    ): void {
        Log::warning('platform.support.escalation', $this->context($request, [
            'support_agent_id' => $supportAgent->id,
            'escalation_type' => $escalationType,
            'metadata' => $metadata,
        ]));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function context(Request $request, array $context): array
    {
        return [
            ...$this->traceContext->current()->toArray(),
            'request_id' => $request->header('X-Request-ID'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            ...$context,
        ];
    }
}
