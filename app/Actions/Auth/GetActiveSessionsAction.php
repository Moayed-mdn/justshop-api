<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\GetActiveSessionsDTO;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * GetActiveSessionsAction
 *
 * Returns all active database sessions for the authenticated user.
 * Marks the current session so the frontend can highlight it.
 *
 * Architecture rules:
 * - No authorization logic here — controller owns that.
 * - Scoped strictly to the authenticated user's sessions.
 * - Session payload is NOT decoded or exposed (contains encrypted data).
 * - Only safe metadata is returned: ip_address, user_agent, last_activity.
 */
class GetActiveSessionsAction
{
    public function execute(GetActiveSessionsDTO $dto): Collection
    {
        $user = User::findOrFail($dto->userId);

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function (object $session) use ($dto): array {
                return [
                    'id'              => $session->id,
                    'ip_address'      => $session->ip_address,
                    'user_agent'      => $session->user_agent,
                    'last_active_at'  => date('Y-m-d\TH:i:s\Z', $session->last_activity),
                    'is_current'      => $dto->currentSessionId !== null
                        && $session->id === $dto->currentSessionId,
                ];
            });
    }
}
