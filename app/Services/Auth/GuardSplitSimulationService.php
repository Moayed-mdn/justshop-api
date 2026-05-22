<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Simulation\GuardTransitionScenario;
use App\DTOs\Auth\Simulation\SessionCollisionAnalysis;
use App\DTOs\Auth\Simulation\SimulatedGuardOwnershipResult;

class GuardSplitSimulationService
{
    /**
     * @return GuardTransitionScenario[]
     */
    public function scenarios(): array
    {
        return [
            new GuardTransitionScenario(
                key: 'merchant_only_session_ownership',
                description: 'Merchant-only operational ownership simulation.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_users',
                primaryOnboardingApplicable: true,
                includesBootstrap: true,
            ),
            new GuardTransitionScenario(
                key: 'customer_only_session_ownership',
                description: 'Customer-only storefront ownership simulation.',
                primaryAuthDomain: 'customer',
                primaryRouteDomain: 'customer_account',
                includesBootstrap: true,
            ),
            new GuardTransitionScenario(
                key: 'dual_session_coexistence',
                description: 'Merchant and customer sessions coexisting across tabs.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_users',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'customer_account',
                primaryOnboardingApplicable: true,
                multiTab: true,
                includesBootstrap: true,
                includesLogout: true,
                includesCsrfRefresh: true,
            ),
            new GuardTransitionScenario(
                key: 'cross_domain_navigation',
                description: 'Cross-domain navigation from merchant to storefront and back.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_admin',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'customer_account',
                crossDomainNavigation: true,
                includesBootstrap: true,
                includesCsrfRefresh: true,
            ),
            new GuardTransitionScenario(
                key: 'session_migration_paths',
                description: 'Future migration from shared session authority to split ownership.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_users',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'customer_account',
                includesSessionMigration: true,
                includesLogout: true,
                includesCsrfRefresh: true,
            ),
            new GuardTransitionScenario(
                key: 'merchant_plus_storefront_tabs',
                description: 'Merchant admin and storefront account open simultaneously in separate tabs.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_admin',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'customer_account',
                multiTab: true,
                includesBootstrap: true,
                includesCsrfRefresh: true,
            ),
            new GuardTransitionScenario(
                key: 'merchant_login_while_storefront_authenticated',
                description: 'Merchant login while customer storefront session already exists.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_users',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'customer_account',
                includesSessionMigration: true,
                includesBootstrap: true,
            ),
            new GuardTransitionScenario(
                key: 'storefront_login_while_merchant_authenticated',
                description: 'Customer storefront login while merchant session already exists.',
                primaryAuthDomain: 'customer',
                primaryRouteDomain: 'customer_account',
                secondaryAuthDomain: 'merchant',
                secondaryRouteDomain: 'merchant_users',
                includesSessionMigration: true,
                includesBootstrap: true,
            ),
            new GuardTransitionScenario(
                key: 'logout_one_context_other_remains_active',
                description: 'Future split-safe logout where one context should remain active.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_users',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'customer_account',
                includesLogout: true,
                multiTab: true,
            ),
            new GuardTransitionScenario(
                key: 'csrf_refresh_during_mixed_context_usage',
                description: 'CSRF refresh while merchant and customer contexts are mixed.',
                primaryAuthDomain: 'merchant',
                primaryRouteDomain: 'merchant_users',
                secondaryAuthDomain: 'customer',
                secondaryRouteDomain: 'customer_account',
                includesCsrfRefresh: true,
                multiTab: true,
            ),
        ];
    }

    public function simulateScenario(GuardTransitionScenario $scenario): SimulatedGuardOwnershipResult
    {
        $primaryFutureGuard = $this->futureGuard($scenario->primaryAuthDomain);
        $secondaryFutureGuard = $scenario->secondaryAuthDomain !== null
            ? $this->futureGuard($scenario->secondaryAuthDomain)
            : null;

        $conflicts = [];
        $notes = [];
        $contaminationRisk = 5;

        $ambiguousOwnership = $secondaryFutureGuard !== null && $primaryFutureGuard !== $secondaryFutureGuard;

        if ($ambiguousOwnership) {
            $contaminationRisk += 35;
            $conflicts[] = 'future_guard_ambiguity';
            $notes[] = 'Scenario requires concurrent ownership across different future guards.';
        }

        $crossDomainNavigationRisk = $scenario->crossDomainNavigation || ($scenario->secondaryRouteDomain !== null && $scenario->primaryRouteDomain !== $scenario->secondaryRouteDomain);

        if ($crossDomainNavigationRisk) {
            $contaminationRisk += 20;
            $conflicts[] = 'cross_domain_navigation';
        }

        $logoutConflict = $scenario->includesLogout && $secondaryFutureGuard !== null;
        if ($logoutConflict) {
            $contaminationRisk += 15;
            $conflicts[] = 'logout_scope_conflict';
            $notes[] = 'Current shared logout invalidates the entire shared session.';
        }

        $csrfConflict = $scenario->includesCsrfRefresh && $secondaryFutureGuard !== null;
        if ($csrfConflict) {
            $contaminationRisk += 10;
            $conflicts[] = 'csrf_scope_conflict';
        }

        $bootstrapOwnershipConflict = $scenario->includesBootstrap && $secondaryFutureGuard !== null;
        if ($bootstrapOwnershipConflict) {
            $contaminationRisk += 10;
            $conflicts[] = 'bootstrap_ownership_conflict';
        }

        if ($scenario->includesSessionMigration) {
            $contaminationRisk += 10;
            $conflicts[] = 'session_migration_complexity';
        }

        if ($scenario->primaryOnboardingApplicable && $scenario->secondaryAuthDomain === 'customer') {
            $contaminationRisk += 5;
            $conflicts[] = 'onboarding_context_overlap';
        }

        $contaminationRisk = min(100, $contaminationRisk);

        return new SimulatedGuardOwnershipResult(
            scenarioKey: $scenario->key,
            primaryFutureGuard: $primaryFutureGuard,
            secondaryFutureGuard: $secondaryFutureGuard,
            ambiguousOwnership: $ambiguousOwnership,
            contaminationRisk: $contaminationRisk,
            logoutConflict: $logoutConflict,
            csrfConflict: $csrfConflict,
            bootstrapOwnershipConflict: $bootstrapOwnershipConflict,
            crossDomainNavigationRisk: $crossDomainNavigationRisk,
            conflicts: array_values(array_unique($conflicts)),
            notes: array_values(array_unique($notes)),
        );
    }

    public function analyzeCollision(GuardTransitionScenario $scenario): SessionCollisionAnalysis
    {
        $simulation = $this->simulateScenario($scenario);
        $collisionVectors = [];

        if ($simulation->ambiguousOwnership) {
            $collisionVectors[] = 'future_guard_ambiguity';
        }

        if ($scenario->multiTab) {
            $collisionVectors[] = 'browser_multi_tab_shared_cookie';
        }

        if ($simulation->logoutConflict) {
            $collisionVectors[] = 'logout_propagation_collision';
        }

        if ($simulation->csrfConflict) {
            $collisionVectors[] = 'csrf_refresh_collision';
        }

        if ($simulation->bootstrapOwnershipConflict) {
            $collisionVectors[] = 'bootstrap_contract_collision';
        }

        return new SessionCollisionAnalysis(
            scenarioKey: $scenario->key,
            collisionDetected: $collisionVectors !== [],
            contaminationSeverityScore: $simulation->contaminationRisk,
            browserMultiTabRisk: $scenario->multiTab ? min(100, 50 + $simulation->contaminationRisk) : 20,
            mobileClientRisk: $scenario->crossDomainNavigation ? 60 : 35,
            logoutPropagationRisk: $simulation->logoutConflict ? 85 : 25,
            csrfRefreshRisk: $simulation->csrfConflict ? 75 : 20,
            collisionVectors: $collisionVectors,
            splitSafeLogoutMap: [
                'merchant_guard' => 'invalidate_merchant_scope_only',
                'customer_guard' => 'invalidate_customer_scope_only',
                'shared_guard' => 'legacy_full_session_invalidation',
            ],
        );
    }

    /**
     * @return array<int, array{scenario: array<string, mixed>, ownership: array<string, mixed>, collision: array<string, mixed>}>
     */
    public function simulateAll(): array
    {
        return array_map(function (GuardTransitionScenario $scenario): array {
            return [
                'scenario' => $scenario->toArray(),
                'ownership' => $this->simulateScenario($scenario)->toArray(),
                'collision' => $this->analyzeCollision($scenario)->toArray(),
            ];
        }, $this->scenarios());
    }

    private function futureGuard(string $authDomain): string
    {
        return match ($authDomain) {
            'customer' => 'customer_guard',
            'merchant', 'platform' => 'merchant_guard',
            default => 'shared_guard',
        };
    }
}
