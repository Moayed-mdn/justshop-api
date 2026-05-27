<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Simulation\SessionCollisionAnalysis;
use App\DTOs\Auth\Simulation\GuardTransitionScenario;
use App\DTOs\Auth\Simulation\SimulatedGuardOwnershipResult;
use App\DTOs\Auth\Session\SessionOwnershipContext;
use Illuminate\Support\Facades\Log;

class GuardSplitSimulationService
{
    public function __construct(
        private readonly TransitionalGuardResolver $guardResolver,
    ) {}

    public function simulate(SessionOwnershipContext $context): array
    {
        if (!config('features.auth.guard_split.shadow.default')) {
            return [];
        }

        $intended = $this->guardResolver->resolve($context);
        $current = 'web'; // Legacy assumption

        $isParity = $intended->guard === $current;

        $simulation = [
            'intended_guard' => $intended->guard,
            'current_guard' => $current,
            'is_parity' => $isParity,
            'auth_domain' => $context->authDomain,
            'route_domain' => $context->routeDomain,
        ];

        $this->logParity($simulation, $context);

        return $simulation;
    }

    private function logParity(array $simulation, SessionOwnershipContext $context): void
    {
        Log::info('auth.guard.split_simulation', [
            'intended_guard' => $simulation['intended_guard'],
            'current_guard' => $simulation['current_guard'],
            'is_parity' => $simulation['is_parity'],
            'session_id' => $context->sessionId,
            'actor_id' => $context->actorId,
            'route_domain' => $context->routeDomain,
        ]);

        if (!$simulation['is_parity']) {
            Log::warning('auth.guard.split_mismatch_detected', [
                'intended_guard' => $simulation['intended_guard'],
                'current_guard' => $simulation['current_guard'],
                'session_id' => $context->sessionId,
                'actor_id' => $context->actorId,
                'route_domain' => $context->routeDomain,
            ]);
        }
    }

    /**
     * @return GuardTransitionScenario[]
     */
    public function scenarios(): array
    {
        return [
            new GuardTransitionScenario(
                key: 'dual_session_coexistence',
                description: 'Merchant and Storefront tabs active in the same browser session.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_users',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'storefront_commerce',
                multiTab: true,
            ),
            new GuardTransitionScenario(
                key: 'logout_one_context_other_remains_active',
                description: 'Merchant logs out while storefront session remains active.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_users',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'storefront_commerce',
                multiTab: true,
                includesLogout: true,
            ),
            new GuardTransitionScenario(
                key: 'csrf_refresh_during_mixed_context_usage',
                description: 'CSRF token refresh while both merchant and customer domains are used.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_users',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'storefront_commerce',
                includesCsrfRefresh: true,
            ),
        ];
    }

    public function simulateScenario(GuardTransitionScenario $scenario): SimulatedGuardOwnershipResult
    {
        $primaryGuard = $scenario->primaryAuthDomain === 'merchant' ? 'merchant_guard' : 'customer_guard';
        $secondaryGuard = $scenario->secondaryAuthDomain ? ($scenario->secondaryAuthDomain === 'merchant' ? 'merchant_guard' : 'customer_guard') : null;

        $ambiguous = $scenario->multiTab || ($scenario->secondaryAuthDomain !== null);
        $logoutConflict = $scenario->includesLogout || $scenario->multiTab;
        $csrfConflict = $scenario->includesCsrfRefresh || $scenario->multiTab;

        return new SimulatedGuardOwnershipResult(
            scenarioKey: $scenario->key,
            primaryFutureGuard: $primaryGuard,
            secondaryFutureGuard: $secondaryGuard,
            ambiguousOwnership: $ambiguous,
            contaminationRisk: $ambiguous ? 80 : 0,
            logoutConflict: $logoutConflict,
            csrfConflict: $csrfConflict,
            bootstrapOwnershipConflict: $scenario->includesBootstrap,
            crossDomainNavigationRisk: $scenario->crossDomainNavigation,
            conflicts: $ambiguous ? ['session_overlap', 'cookie_collision'] : [],
            notes: [],
        );
    }

    public function analyzeCollision(GuardTransitionScenario $scenario): SessionCollisionAnalysis
    {
        $collisionDetected = $scenario->multiTab || $scenario->includesLogout || $scenario->includesCsrfRefresh;
        
        $vectors = [];
        if ($scenario->multiTab) $vectors[] = 'browser_multi_tab_shared_cookie';
        if ($scenario->includesLogout) $vectors[] = 'logout_propagation_collision';
        if ($scenario->includesCsrfRefresh) $vectors[] = 'csrf_token_refresh_overlap';

        return new SessionCollisionAnalysis(
            scenarioKey: $scenario->key,
            collisionDetected: $collisionDetected,
            contaminationSeverityScore: $collisionDetected ? 75 : 0,
            browserMultiTabRisk: $scenario->multiTab ? 80 : 0,
            mobileClientRisk: 0,
            logoutPropagationRisk: $scenario->includesLogout ? 90 : 0,
            csrfRefreshRisk: $scenario->includesCsrfRefresh ? 85 : 0,
            collisionVectors: $vectors,
            splitSafeLogoutMap: [
                'merchant_guard' => 'invalidate_merchant_scope_only',
                'customer_guard' => 'invalidate_customer_scope_only',
            ],
        );
    }

    /**
     * @return array<int, array{scenario: array<string, mixed>, ownership: array<string, mixed>, collision: array<string, mixed>}>
     */
    public function simulateAll(): array
    {
        $results = [];
        foreach ($this->scenarios() as $scenario) {
            $ownership = $this->simulateScenario($scenario);
            $collision = $this->analyzeCollision($scenario);
            
            $results[] = [
                'scenario' => [
                    'key' => $scenario->key,
                    'description' => $scenario->description,
                ],
                'ownership' => $ownership->toArray(),
                'collision' => $collision->toArray(),
            ];
        }
        return $results;
    }
}
