<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\GetActiveSessionsAction;
use App\Actions\Auth\LogoutAllDevicesAction;
use App\Actions\Auth\LogoutSessionAction;
use App\DTOs\Auth\GetActiveSessionsDTO;
use App\DTOs\Auth\LogoutAllDevicesDTO;
use App\DTOs\Auth\LogoutSessionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GetActiveSessionsRequest;
use App\Http\Requests\Auth\LogoutAllDevicesRequest;
use App\Http\Requests\Auth\LogoutSessionRequest;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponserTrait;

/**
 * SessionController
 *
 * Manages active session visibility and multi-device logout.
 *
 * Architecture rules:
 * - Thin controller: delegates all logic to Actions.
 * - No business logic here.
 * - Password confirmation is enforced at the FormRequest layer.
 * - Authorization is implicit: users can only see/revoke their own sessions
 *   (enforced by scoping queries to the authenticated user's ID in the Action).
 */
class SessionController extends Controller
{
    use ApiResponserTrait;

    public function __construct(
        private readonly GetActiveSessionsAction $getActiveSessionsAction,
        private readonly LogoutAllDevicesAction $logoutAllDevicesAction,
        private readonly LogoutSessionAction $logoutSessionAction,
    ) {}

    /**
     * GET /api/v1/users/sessions
     *
     * Returns all active sessions for the authenticated user.
     * The current session is flagged with is_current = true.
     */
    public function index(GetActiveSessionsRequest $request): JsonResponse
    {
        $sessions = $this->getActiveSessionsAction->execute(
            GetActiveSessionsDTO::fromRequest($request)
        );

        return $this->success(
            \App\Http\Resources\Auth\SessionResource::collection($sessions),
            __('auth.sessions_retrieved')
        );
    }

    /**
     * DELETE /api/v1/users/sessions
     *
     * Revokes all sessions except the current one.
     * Requires password confirmation (enforced in FormRequest).
     */
    public function destroyAll(LogoutAllDevicesRequest $request): JsonResponse
    {
        $result = $this->logoutAllDevicesAction->execute(
            LogoutAllDevicesDTO::fromRequest($request)
        );

        return $this->success($result, __('auth.all_other_sessions_revoked'));
    }

    /**
     * DELETE /api/v1/users/sessions/{id}
     *
     * Revokes a single session.
     */
    public function destroy(LogoutSessionRequest $request, string $sessionId): JsonResponse
    {
        $this->logoutSessionAction->execute(
            LogoutSessionDTO::fromRequest($request, $sessionId)
        );

        return $this->success(null, __('auth.session_revoked'));
    }
}
