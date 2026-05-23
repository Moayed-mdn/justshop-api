<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Auth\ActorContextEnum;
use App\Enums\Auth\AuthDomainEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Multi-Session Governance Service
 * 
 * Wave 6: Prepare long-term browser/device coexistence safety.
 * 
 * Support safe coexistence for:
 * - Merchant session
 * - Customer session
 * - Support session
 * - Multiple browser tabs
 * - Multiple devices
 * 
 * WITHOUT contamination.
 */
class MultiSessionGovernanceService
{
    public function detectCoexistence(Request $request): array
    {
        $sessionId = $request->session()->getId();
        $authDomain = $request->session()->get('auth_domain');
        $actorType = $request->session()->get('actor_type');
        $actorId = $request->session()->get('actor_id');

        return [
            'session_id' => $sessionId,
            'auth_domain' => $authDomain,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'coexistence_risk' => $this->assessCoexistenceRisk($request),
        ];
    }

    public function assessCoexistenceRisk(Request $request): string
    {
        $authDomain = $request->session()->get('auth_domain');
        $actorType = $request->session()->get('actor_type');

        // Check for impossible actor combinations
        if ($authDomain === AuthDomainEnum::CUSTOMER->value && $actorType === ActorContextEnum::MERCHANT->value) {
            return 'high_impossible_combination';
        }

        if ($authDomain === AuthDomainEnum::MERCHANT->value && $actorType === ActorContextEnum::CUSTOMER->value) {
            return 'high_impossible_combination';
        }

        // Check for support impersonation remnants
        if ($actorType === ActorContextEnum::SUPPORT_AGENT->value && $authDomain !== AuthDomainEnum::PLATFORM->value) {
            return 'medium_impersonation_remnant';
        }

        return 'low';
    }

    public function detectAbnormalCoexistence(Request $request): ?array
    {
        $risk = $this->assessCoexistenceRisk($request);

        if (in_array($risk, ['high_impossible_combination', 'medium_impersonation_remnant'], true)) {
            $anomaly = [
                'risk_level' => $risk,
                'session_id' => $request->session()->getId(),
                'auth_domain' => $request->session()->get('auth_domain'),
                'actor_type' => $request->session()->get('actor_type'),
                'actor_id' => $request->session()->get('actor_id'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            Log::error('session.coexistence.abnormal_detected', $anomaly);

            return $anomaly;
        }

        return null;
    }

    public function getDeviceSessionOwnership(Request $request): array
    {
        // Wave 6: Basic device tracking
        // Future: Device-aware sessions, actor-bound devices, session lineage
        return [
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
            'session_id' => $request->session()->getId(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    private function generateDeviceFingerprint(Request $request): string
    {
        return hash('sha256', $request->ip() . $request->userAgent());
    }

    public function getConcurrentSessionGovernance(int $userId): array
    {
        // Wave 6: Preparation only
        // Future: Track concurrent sessions per user, enforce limits
        return [
            'concurrent_sessions_enabled' => false,
            'max_concurrent_sessions' => null,
            'current_sessions_count' => 'unknown',
        ];
    }
}
