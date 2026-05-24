<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\LogoutAllDevicesDTO;
use App\Models\User;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * LogoutAllDevicesAction
 *
 * Invalidates all active sessions for the authenticated user except the
 * current one (optional). Requires password confirmation for security.
 *
 * Architecture rules:
 * - No authorization logic here — controller owns that via policy.
 * - Password verification is done in the FormRequest (current_password rule).
 * - Session deletion is scoped strictly to the authenticated user's user_id.
 * - The current session is preserved by default to avoid locking the user out.
 */
class LogoutAllDevicesAction
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(LogoutAllDevicesDTO $dto): array
    {
        $user = User::findOrFail($dto->userId);

        $deletedCount = DB::transaction(function () use ($user, $dto): int {
            $query = DB::table('sessions')->where('user_id', $user->id);

            // Preserve the current session so the user stays logged in on this device.
            if ($dto->currentSessionId !== null) {
                $query->where('id', '!=', $dto->currentSessionId);
            }

            $count = $query->count();
            $query->delete();

            $this->auditLogger->record(
                event: 'auth.logout_all_devices',
                metadata: [
                    'user_id'           => $user->id,
                    'sessions_revoked'  => $count,
                    'current_preserved' => $dto->currentSessionId !== null,
                ],
            );

            return $count;
        });

        return [
            'sessions_revoked' => $deletedCount,
        ];
    }
}
