<?php

declare(strict_types=1);

namespace App\Services\Platform\Impersonation;

use App\Enums\Platform\ImpersonationStatusEnum;
use App\Models\Impersonation;
use App\Models\User;
use App\Services\Platform\PlatformTelemetry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Impersonation Lifecycle Manager
 * 
 * Wave 6: GOVERNED impersonation only.
 * NOT unrestricted impersonation.
 * 
 * Every impersonation MUST include:
 * - Actor initiating impersonation
 * - Target actor
 * - Approval requirements (future)
 * - Reason tracking
 * - Expiration
 * - Revocation capability
 * - Audit persistence
 */
class ImpersonationLifecycleManager
{
    public function __construct(
        private readonly PlatformTelemetry $telemetry,
        private readonly ImpersonationTelemetry $impersonationTelemetry,
    ) {}

    public function request(
        User $initiator,
        User $target,
        string $reason,
        int $durationMinutes = 60,
        ?string $approvalToken = null,
    ): Impersonation {
        return DB::transaction(function () use ($initiator, $target, $reason, $durationMinutes, $approvalToken) {
            $impersonation = Impersonation::create([
                'initiator_id' => $initiator->id,
                'target_id' => $target->id,
                'reason' => $reason,
                'status' => ImpersonationStatusEnum::PENDING->value,
                'requested_at' => now(),
                'expires_at' => now()->addMinutes($durationMinutes),
                'approval_token' => $approvalToken,
            ]);

            $this->impersonationTelemetry->logImpersonationRequested(
                $initiator,
                $target,
                $reason,
                $durationMinutes
            );

            return $impersonation;
        });
    }

    public function activate(Request $request, Impersonation $impersonation): void
    {
        if ($impersonation->status !== ImpersonationStatusEnum::PENDING->value) {
            throw new \RuntimeException('Impersonation is not in pending state.');
        }

        if ($impersonation->expires_at < now()) {
            throw new \RuntimeException('Impersonation request has expired.');
        }

        DB::transaction(function () use ($request, $impersonation) {
            $impersonation->update([
                'status' => ImpersonationStatusEnum::ACTIVE->value,
                'activated_at' => now(),
                'session_id' => $request->session()->getId(),
            ]);

            $this->impersonationTelemetry->logImpersonationActivated(
                $impersonation->initiator,
                $impersonation->target,
                $request
            );
        });
    }

    public function terminate(Request $request, Impersonation $impersonation, string $reason): void
    {
        if ($impersonation->status !== ImpersonationStatusEnum::ACTIVE->value) {
            throw new \RuntimeException('Impersonation is not active.');
        }

        DB::transaction(function () use ($request, $impersonation, $reason) {
            $impersonation->update([
                'status' => ImpersonationStatusEnum::TERMINATED->value,
                'terminated_at' => now(),
                'termination_reason' => $reason,
            ]);

            $this->impersonationTelemetry->logImpersonationTerminated(
                $impersonation->initiator,
                $impersonation->target,
                $reason,
                $request
            );
        });
    }

    public function expire(Impersonation $impersonation): void
    {
        if ($impersonation->status !== ImpersonationStatusEnum::ACTIVE->value) {
            return;
        }

        DB::transaction(function () use ($impersonation) {
            $impersonation->update([
                'status' => ImpersonationStatusEnum::EXPIRED->value,
                'terminated_at' => now(),
                'termination_reason' => 'automatic_expiration',
            ]);

            $this->impersonationTelemetry->logImpersonationExpired(
                $impersonation->initiator,
                $impersonation->target
            );
        });
    }

    public function getActive(User $initiator): ?Impersonation
    {
        return Impersonation::where('initiator_id', $initiator->id)
            ->where('status', ImpersonationStatusEnum::ACTIVE->value)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function hasActiveImpersonation(Request $request): bool
    {
        if (!$request->hasSession()) {
            return false;
        }

        return Impersonation::where('session_id', $request->session()->getId())
            ->where('status', ImpersonationStatusEnum::ACTIVE->value)
            ->where('expires_at', '>', now())
            ->exists();
    }
}
