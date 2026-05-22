<?php

declare(strict_types=1);

namespace App\Services\Auth\Drift;

use App\Services\Auth\Policy\PolicyOwnershipReportService;

class AuthorizationOwnershipTriageService
{
    public function __construct(
        private readonly AuthorizationDriftReportService $driftReportService,
        private readonly PolicyOwnershipReportService $policyOwnershipReportService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(?string $allowlistPath = null, ?string $baselinePath = null): array
    {
        $driftReport = $this->driftReportService->generate($allowlistPath, $baselinePath);
        $ownershipReport = $this->policyOwnershipReportService->generate();

        $genericCurrentStoreFindings = array_values(array_filter(
            $driftReport['active_findings'] ?? [],
            fn (array $finding): bool => ($finding['type'] ?? null) === 'generic_current_store_authorize',
        ));

        $triagedFindings = [];

        foreach ($genericCurrentStoreFindings as $index => $finding) {
            $classification = $this->classifyGenericCurrentStoreFinding((string) ($finding['file'] ?? ''));
            $triagedFindings[] = [
                'priority' => $index + 1,
                'file' => $finding['file'],
                'line' => $finding['line'],
                'classification' => $classification['classification'],
                'domain' => $classification['domain'],
                'reason' => $classification['reason'],
                'recommended_next_step' => $classification['recommended_next_step'],
                'wave' => $classification['wave'],
            ];
        }

        $compatibilityBridges = array_values(array_map(
            fn (array $entry): array => [
                'route_uri' => $entry['route_uri'],
                'controller' => $entry['controller'],
                'controller_method' => $entry['controller_method'],
                'domain' => $entry['domain'],
                'reason' => 'explicit compatibility bridge or non-Wave-2.5 ownership fallback remains',
            ],
            array_filter(
                $ownershipReport['entries'] ?? [],
                fn (array $entry): bool => (($entry['domain'] ?? null) === 'cms_blog' && ($entry['policy_invoked'] ?? false) === true)
                    || (($entry['domain'] ?? null) === 'membership_admin')
            )
        ));

        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'generic_current_store_findings' => count($genericCurrentStoreFindings),
                'classifications' => $this->countBy($triagedFindings, 'classification'),
                'compatibility_bridges' => count($compatibilityBridges),
            ],
            'migration_priority_order' => [
                ['priority' => 1, 'domain' => 'brand', 'status' => 'safe_to_normalize_now'],
                ['priority' => 2, 'domain' => 'tag', 'status' => 'safe_to_normalize_now'],
                ['priority' => 3, 'domain' => 'category', 'status' => 'safe_to_normalize_now'],
                ['priority' => 4, 'domain' => 'cms_blog', 'status' => 'false_positive_or_compatibility_bridge'],
                ['priority' => 5, 'domain' => 'dashboard', 'status' => 'safe_to_normalize_now'],
                ['priority' => 6, 'domain' => 'product', 'status' => 'requires_rbac_normalization_later'],
                ['priority' => 7, 'domain' => 'membership_admin', 'status' => 'requires_membership_evolution_later'],
                ['priority' => 8, 'domain' => 'order', 'status' => 'requires_wave_3_context'],
            ],
            'generic_current_store_triage' => $triagedFindings,
            'compatibility_bridges' => $compatibilityBridges,
        ];
    }

    /**
     * @return array{classification: string, domain: string, reason: string, recommended_next_step: string, wave: string}
     */
    private function classifyGenericCurrentStoreFinding(string $file): array
    {
        return match (true) {
            str_contains($file, '/Admin/Brand/') => [
                'classification' => 'safe_to_normalize_now',
                'domain' => 'brand',
                'reason' => 'store-admin CRUD domain already has stable permission middleware and store scoping',
                'recommended_next_step' => 'move controller ownership to BrandPolicy and keep middleware as coarse gate',
                'wave' => 'wave_2_5',
            ],
            str_contains($file, '/Admin/Tag/') => [
                'classification' => 'safe_to_normalize_now',
                'domain' => 'tag',
                'reason' => 'hidden permission checks can be moved into TagPolicy with explicit route ownership',
                'recommended_next_step' => 'replace hidden request permission checks with TagPolicy and explicit route middleware',
                'wave' => 'wave_2_5',
            ],
            str_contains($file, '/Admin/Category/') => [
                'classification' => 'safe_to_normalize_now',
                'domain' => 'category',
                'reason' => 'store-admin CRUD domain already has stable permission middleware and store scoping',
                'recommended_next_step' => 'move controller ownership to CategoryPolicy and keep middleware as coarse gate',
                'wave' => 'wave_2_5',
            ],
            str_contains($file, '/Admin/Dashboard/') => [
                'classification' => 'safe_to_normalize_now',
                'domain' => 'dashboard',
                'reason' => 'read-only dashboard paths are stable and already permission-gated',
                'recommended_next_step' => 'move controller ownership to DashboardPolicy and keep middleware as coarse gate',
                'wave' => 'wave_2_5',
            ],
            str_contains($file, '/Admin/Product/') => [
                'classification' => 'requires_rbac_normalization_later',
                'domain' => 'product',
                'reason' => 'product authorization remains coupled to broader inventory permission normalization',
                'recommended_next_step' => 'defer to RBAC normalization after safe-domain ownership is stable',
                'wave' => 'rbac_normalization',
            ],
            str_contains($file, '/Admin/User/') => [
                'classification' => 'requires_membership_evolution_later',
                'domain' => 'membership_admin',
                'reason' => 'admin user-management still fronts membership compatibility behavior',
                'recommended_next_step' => 'defer to membership evolution and ownership split work',
                'wave' => 'membership_evolution',
            ],
            str_contains($file, '/Admin/Order/') => [
                'classification' => 'requires_wave_3_context',
                'domain' => 'order',
                'reason' => 'order ownership remains coupled to checkout and customer identity behavior',
                'recommended_next_step' => 'defer until Wave 3 identity separation and checkout ownership work',
                'wave' => 'wave_3',
            ],
            default => [
                'classification' => 'false_positive_or_compatibility_bridge',
                'domain' => 'unknown',
                'reason' => 'route remains in a compatibility bridge not targeted for Wave 2.5 normalization',
                'recommended_next_step' => 'keep observable and reassess after safe-domain ownership stabilization',
                'wave' => 'compatibility_window',
            ],
        };
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, int>
     */
    private function countBy(array $rows, string $key): array
    {
        $counts = [];

        foreach ($rows as $row) {
            $value = (string) ($row[$key] ?? 'unknown');
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }
}
