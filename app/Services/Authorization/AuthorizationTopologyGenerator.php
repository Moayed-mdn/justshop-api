<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

/**
 * Authorization Topology Generator
 * 
 * Wave 6: Generate authorization topology artifacts.
 * 
 * Generate:
 * - policy-domain-map.json
 * - actor-authority-map.json
 * - escalation-boundary-report.json
 */
class AuthorizationTopologyGenerator
{
    public function __construct(
        private readonly PolicyOwnershipRegistry $registry,
    ) {}

    public function generate(): void
    {
        $this->generatePolicyDomainMap();
        $this->generateActorAuthorityMap();
        $this->generateEscalationBoundaryReport();
    }

    private function generatePolicyDomainMap(): void
    {
        $policies = $this->registry->getAll();

        $map = [
            'generated_at' => now()->toIso8601String(),
            'policy_count' => count($policies),
            'policies' => $policies,
        ];

        $path = storage_path('app/architecture/policy-domain-map.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($map, JSON_PRETTY_PRINT));
    }

    private function generateActorAuthorityMap(): void
    {
        $policies = $this->registry->getAll();

        $actorMap = [];

        foreach ($policies as $policyClass => $metadata) {
            foreach ($metadata['supported_actor_domains'] as $domain) {
                if (!isset($actorMap[$domain])) {
                    $actorMap[$domain] = [];
                }

                $actorMap[$domain][] = $policyClass;
            }
        }

        $map = [
            'generated_at' => now()->toIso8601String(),
            'actor_domains' => $actorMap,
        ];

        $path = storage_path('app/architecture/actor-authority-map.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($map, JSON_PRETTY_PRINT));
    }

    private function generateEscalationBoundaryReport(): void
    {
        $policies = $this->registry->getAll();

        $escalations = [];

        foreach ($policies as $policyClass => $metadata) {
            if (!empty($metadata['escalation_rules'])) {
                $escalations[$policyClass] = $metadata['escalation_rules'];
            }
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'escalation_count' => count($escalations),
            'escalations' => $escalations,
        ];

        $path = storage_path('app/architecture/escalation-boundary-report.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT));
    }
}
