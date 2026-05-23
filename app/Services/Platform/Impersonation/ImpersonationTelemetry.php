<?php

declare(strict_types=1);

namespace App\Services\Platform\Impersonation;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Impersonation Telemetry
 * 
 * Wave 6: Every impersonation action MUST emit telemetry.
 * 
 * Telemetry MUST include:
 * - Initiator
 * - Target
 * - Route
 * - Session ownership
 * - Guard ownership
 * - Duration
 * - Termination reason
 */
class ImpersonationTelemetry
{
    public function logImpersonationRequested(
        User $initiator,
        User $target,
        string $reason,
        int $durationMinutes,
    ): void {
        Log::warning('platform.impersonation.requested', [
            'initiator_id' => $initiator->id,
            'initiator_actor_type' => $initiator->getActorContext()->value,
            'target_id' => $target->id,
            'target_actor_type' => $target->getActorContext()->value,
            'reason' => $reason,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    public function logImpersonationActivated(
        User $initiator,
        User $target,
        Request $request,
    ): void {
        Log::warning('platform.impersonation.activated', [
            'initiator_id' => $initiator->id,
            'initiator_actor_type' => $initiator->getActorContext()->value,
            'target_id' => $target->id,
            'target_actor_type' => $target->getActorContext()->value,
            'session_id' => $request->session()->getId(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function logImpersonationTerminated(
        User $initiator,
        User $target,
        string $reason,
        Request $request,
    ): void {
        Log::info('platform.impersonation.terminated', [
            'initiator_id' => $initiator->id,
            'initiator_actor_type' => $initiator->getActorContext()->value,
            'target_id' => $target->id,
            'target_actor_type' => $target->getActorContext()->value,
            'reason' => $reason,
            'session_id' => $request->session()->getId(),
        ]);
    }

    public function logImpersonationExpired(
        User $initiator,
        User $target,
    ): void {
        Log::info('platform.impersonation.expired', [
            'initiator_id' => $initiator->id,
            'initiator_actor_type' => $initiator->getActorContext()->value,
            'target_id' => $target->id,
            'target_actor_type' => $target->getActorContext()->value,
        ]);
    }

    public function logImpersonationRouteAccess(
        User $initiator,
        User $target,
        Request $request,
    ): void {
        Log::info('platform.impersonation.route_accessed', [
            'initiator_id' => $initiator->id,
            'target_id' => $target->id,
            'route' => $request->path(),
            'method' => $request->method(),
            'session_id' => $request->session()->getId(),
        ]);
    }

    public function logImpersonationViolation(
        string $violationType,
        Request $request,
        array $metadata = [],
    ): void {
        Log::error('platform.impersonation.violation', [
            'violation_type' => $violationType,
            'route' => $request->path(),
            'session_id' => $request->session()->getId(),
            'ip' => $request->ip(),
            ...$metadata,
        ]);
    }
}
