<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Services\Auth\ProviderGovernanceService;
use App\Services\Auth\TransitionalDependencyAnalyzer;
use App\Services\Authorization\AuthorizationTopologyGenerator;
use App\Services\Authorization\PolicyOwnershipRegistry;
use App\Services\Enterprise\EnterpriseMembershipReadinessService;
use App\Services\Platform\PlatformAuthorityResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Wave 6 Artifact Generator
 *
 * php artisan architecture:wave6-artifacts
 *
 * Generates all required Wave 6 machine-readable artifacts:
 * - platform-authority-report.json
 * - impersonation-governance-report.json
 * - provider-readiness-report.json
 * - enterprise-membership-readiness.json
 * - transitional-debt-report.json
 * - policy-domain-map.json
 * - actor-authority-map.json
 * - escalation-boundary-report.json
 */
class GenerateWave6ArtifactsCommand extends Command
{
    protected $signature = 'architecture:wave6-artifacts';

    protected $description = 'Wave 6: Generate all machine-readable governance artifacts';

    public function handle(
        ProviderGovernanceService $providerGovernance,
        TransitionalDependencyAnalyzer $transitionalAnalyzer,
        EnterpriseMembershipReadinessService $enterpriseReadiness,
        PolicyOwnershipRegistry $policyRegistry,
        AuthorizationTopologyGenerator $topologyGenerator,
    ): int {
        $basePath = storage_path('app/architecture');
        File::ensureDirectoryExists($basePath);

        $this->generatePlatformAuthorityReport($basePath);
        $this->generateImpersonationGovernanceReport($basePath);
        $this->generateProviderReadinessReport($basePath, $providerGovernance);
        $this->generateEnterpriseMembershipReadiness($basePath, $enterpriseReadiness);
        $this->generateTransitionalDebtReport($basePath, $transitionalAnalyzer);
        $topologyGenerator->generate();

        $this->info('✅ Wave 6 artifacts generated in: ' . $basePath);
        $this->table(
            ['Artifact', 'Path'],
            collect([
                'platform-authority-report.json',
                'impersonation-governance-report.json',
                'provider-readiness-report.json',
                'enterprise-membership-readiness.json',
                'transitional-debt-report.json',
                'policy-domain-map.json',
                'actor-authority-map.json',
                'escalation-boundary-report.json',
            ])->map(fn ($f) => [$f, $basePath . '/' . $f])->toArray()
        );

        return 0;
    }

    private function generatePlatformAuthorityReport(string $basePath): void
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'wave' => 6,
            'platform_authority_domain' => [
                'route_prefix' => '/api/v1/platform',
                'middleware' => ['auth:sanctum', 'identity.route:platform,platform,enforce', 'platform.authority:platform_admin'],
                'allowed_actors' => ['super_admin'],
                'authority_enum' => 'platform_admin',
                'route_count' => 14,
                'isolation_status' => 'enforced',
            ],
            'support_authority_domain' => [
                'route_prefix' => '/api/v1/support',
                'middleware' => ['auth:sanctum', 'identity.route:support,platform,enforce', 'support.authority'],
                'allowed_actors' => ['super_admin', 'support_agent'],
                'authority_enum' => 'support_agent',
                'route_count' => 15,
                'isolation_status' => 'enforced',
            ],
            'transitional_legacy_routes' => [
                'description' => 'Admin routes using identity.route:platform without explicit platform.authority middleware',
                'route_prefix' => '/api/v1/admin',
                'migration_status' => 'pending',
                'blocker' => 'admin_route_refactoring_required',
            ],
            'authority_boundaries' => [
                'platform_inherits_merchant' => false,
                'support_inherits_merchant' => false,
                'platform_shares_merchant_policies' => false,
                'platform_shares_merchant_ownership' => false,
            ],
            'telemetry_events' => [
                'platform.route.accessed',
                'platform.support.route_accessed',
                'platform.access.denied',
                'platform.override.executed',
                'platform.support.escalation',
                'identity.platform_access.audited',
            ],
        ];

        File::put($basePath . '/platform-authority-report.json', json_encode($report, JSON_PRETTY_PRINT));
    }

    private function generateImpersonationGovernanceReport(string $basePath): void
    {
        $tableExists = false;
        try {
            $tableExists = \Illuminate\Support\Facades\Schema::hasTable('impersonations');
        } catch (\Throwable) {
            $tableExists = false;
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'wave' => 6,
            'governance_model' => 'governed_only',
            'unrestricted_impersonation' => false,
            'lifecycle_states' => ['pending', 'active', 'terminated', 'expired', 'denied'],
            'required_fields' => ['initiator_id', 'target_id', 'reason', 'expires_at'],
            'audit_persistence' => true,
            'telemetry_events' => [
                'platform.impersonation.requested',
                'platform.impersonation.activated',
                'platform.impersonation.terminated',
                'platform.impersonation.expired',
                'platform.impersonation.route_accessed',
                'platform.impersonation.violation',
            ],
            'infrastructure' => [
                'model_exists' => class_exists(\App\Models\Impersonation::class),
                'lifecycle_manager_exists' => class_exists(\App\Services\Platform\Impersonation\ImpersonationLifecycleManager::class),
                'telemetry_exists' => class_exists(\App\Services\Platform\Impersonation\ImpersonationTelemetry::class),
                'migration_exists' => file_exists(base_path('database/migrations/2026_05_23_000001_create_impersonations_table.php')),
                'table_migrated' => $tableExists,
            ],
            'activation_gate' => 'features.platform.impersonation.enabled',
            'activation_status' => 'gated_off',
            'forbidden_patterns' => [
                'session_swapping_hacks',
                'silent_guard_replacement',
                'hidden_support_elevation',
                'middleware_bypass_impersonation',
                'impersonation_without_audit',
            ],
        ];

        File::put($basePath . '/impersonation-governance-report.json', json_encode($report, JSON_PRETTY_PRINT));
    }

    private function generateProviderReadinessReport(string $basePath, ProviderGovernanceService $service): void
    {
        $readiness = $service->getProviderReadinessReport();

        $report = array_merge([
            'generated_at' => now()->toIso8601String(),
            'wave' => 6,
            'separation_activated' => false,
            'preparation_only' => true,
        ], $readiness);

        File::put($basePath . '/provider-readiness-report.json', json_encode($report, JSON_PRETTY_PRINT));
    }

    private function generateEnterpriseMembershipReadiness(string $basePath, EnterpriseMembershipReadinessService $service): void
    {
        $readiness = $service->getReadinessReport();

        $report = array_merge([
            'generated_at' => now()->toIso8601String(),
            'wave' => 6,
            'lifecycle_vocabulary' => \App\Enums\Enterprise\MembershipLifecycleEnum::values(),
            'ownership_semantics' => \App\Enums\Enterprise\OwnershipSemanticEnum::values(),
            'store_user_lifecycle_column_added' => true,
        ], $readiness);

        File::put($basePath . '/enterprise-membership-readiness.json', json_encode($report, JSON_PRETTY_PRINT));
    }

    private function generateTransitionalDebtReport(string $basePath, TransitionalDependencyAnalyzer $analyzer): void
    {
        $analysis = $analyzer->analyze();
        $debtScore = $analyzer->getTransitionalDebtScore();

        $report = array_merge([
            'generated_at' => now()->toIso8601String(),
            'wave' => 6,
            'debt_score' => $debtScore,
            'debt_threshold_blocker' => 70,
            'is_blocker' => $debtScore > 70,
        ], $analysis);

        File::put($basePath . '/transitional-debt-report.json', json_encode($report, JSON_PRETTY_PRINT));
    }
}
