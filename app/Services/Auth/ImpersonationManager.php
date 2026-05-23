<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Auth\ImpersonationRecord;
use App\Models\User;
use App\Services\Auth\IdentityTelemetry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationManager
{
    private const SESSION_KEY_IMPERSONATOR_ID = 'impersonator_id';
    private const SESSION_KEY_IMPERSONATION_RECORD_ID = 'impersonation_record_id';

    public function __construct(
        private readonly IdentityTelemetry $telemetry,
    ) {}

    public function start(Request $request, User $initiator, User $target, string $reason, ?int $durationMinutes = 60): ImpersonationRecord
    {
        $record = ImpersonationRecord::create([
            'initiator_id' => $initiator->id,
            'target_id' => $target->id,
            'reason' => $reason,
            'expires_at' => $durationMinutes ? now()->addMinutes($durationMinutes) : null,
        ]);

        $request->session()->put(self::SESSION_KEY_IMPERSONATOR_ID, $initiator->id);
        $request->session()->put(self::SESSION_KEY_IMPERSONATION_RECORD_ID, $record->id);

        // Switch guard authority to target
        Auth::login($target);

        $this->telemetry->logImpersonationStarted($request, [
            'initiator_id' => $initiator->id,
            'target_id' => $target->id,
            'impersonation_record_id' => $record->id,
            'reason' => $reason,
        ]);

        return $record;
    }

    public function stop(Request $request, string $reason = 'manual_stop'): void
    {
        $impersonatorId = $request->session()->get(self::SESSION_KEY_IMPERSONATOR_ID);
        $recordId = $request->session()->get(self::SESSION_KEY_IMPERSONATION_RECORD_ID);

        if ($recordId) {
            $record = ImpersonationRecord::find($recordId);
            if ($record) {
                $record->update([
                    'revoked_at' => now(),
                    'revoked_reason' => $reason,
                ]);
            }
        }

        $request->session()->forget([
            self::SESSION_KEY_IMPERSONATOR_ID,
            self::SESSION_KEY_IMPERSONATION_RECORD_ID,
        ]);

        if ($impersonatorId) {
            $impersonator = User::find($impersonatorId);
            if ($impersonator) {
                Auth::login($impersonator);
            }
        }

        $this->telemetry->logImpersonationEnded($request, [
            'initiator_id' => $impersonatorId,
            'record_id' => $recordId,
            'reason' => $reason,
        ]);
    }

    public function isImpersonating(): bool
    {
        return Session::has(self::SESSION_KEY_IMPERSONATOR_ID);
    }

    public function getImpersonatorId(): ?int
    {
        return Session::get(self::SESSION_KEY_IMPERSONATOR_ID);
    }
}
