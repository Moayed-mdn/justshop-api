<?php

declare(strict_types=1);

namespace App\Services\Auth;

class FrontendGuardSplitReadinessService
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(): array
    {
        $unsupportedAssumptions = [
            'shared_session_cookie_still_authoritative',
            'logout_still_invalidates_the_shared_session_scope',
            'single_csrf_cookie_endpoint_still_shared',
            'merchant_auth_routes_remain_authoritative',
            'merchant_bootstrap_remains_authoritative',
        ];

        $supportedPreparations = [
            'additive_session_metadata_present',
            'customer_namespace_present',
            'route_domain_metadata_present',
            'csrf_ownership_headers_present',
            'logout_ownership_tracing_present',
        ];

        $migrationRiskScore = 70;

        return [
            'bootstrap_dependencies' => [
                'merchant_bootstrap_contract_preserved' => true,
                'storefront_bootstrap_isolated' => true,
                'session_metadata_additive' => true,
            ],
            'auth_metadata_usage' => [
                'auth_domain' => true,
                'actor_type' => true,
                'route_domain' => true,
                'onboarding_applicable' => true,
                'future_guard_hint' => true,
            ],
            'route_domain_assumptions' => [
                'merchant_domain_metadata_present' => true,
                'customer_domain_metadata_present' => true,
                'shared_authority_still_in_effect' => true,
            ],
            'csrf_assumptions' => [
                'shared_csrf_behavior_unchanged' => true,
                'ownership_headers_additive' => true,
                'split_cookie_assumptions_not_yet_supported' => true,
            ],
            'logout_assumptions' => [
                'shared_logout_behavior_unchanged' => true,
                'future_scope_analysis_available' => true,
            ],
            'session_persistence_assumptions' => [
                'shared_session_model_still_authoritative' => true,
                'split_persistence_not_supported_yet' => true,
            ],
            'supported_preparations' => $supportedPreparations,
            'unsupported_assumptions' => $unsupportedAssumptions,
            'migration_risk_score' => $migrationRiskScore,
            'status' => $migrationRiskScore >= 70 ? 'attention_required' : 'healthy',
        ];
    }
}
