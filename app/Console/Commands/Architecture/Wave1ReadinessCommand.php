<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Support\FeatureFlags\FeatureFlag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class Wave1ReadinessCommand extends Command
{
    protected $signature = 'architecture:wave1-readiness
                            {--json : Output results as JSON}
                            {--fail-on-blocked : Exit with non-zero code if Wave 1 is blocked}';

    protected $description = 'Generate Wave 1 readiness report and governance gate status';

    private array $report = [];

    public function handle(): int
    {
        $this->info('Wave 1 Readiness Assessment');
        $this->newLine();

        $this->assessSecurityHardening();
        $this->assessFeatureFlagGovernance();
        $this->assessForbiddenPatterns();
        $this->assessDriftDetection();
        $this->assessOperationalFoundations();
        $this->assessQueueObservability();
        $this->assessGovernanceDocumentation();
        $this->determineGateStatus();

        $this->saveReport();

        if ($this->option('json')) {
            $this->line(json_encode($this->report, JSON_PRETTY_PRINT));
        } else {
            $this->displayHumanReadable();
        }

        if ($this->option('fail-on-blocked') && $this->report['wave1_gate']['status'] === 'blocked') {
            return 1;
        }

        return 0;
    }

    private function assessSecurityHardening(): void
    {
        $this->info('Assessing P1: Critical Security Hardening...');

        $forbiddenPatternsOutput = null;
        Artisan::call('architecture:detect-forbidden-patterns', ['--json' => true], $forbiddenPatternsOutput);
        $forbiddenPatterns = json_decode(Artisan::output(), true);

        $sensitiveLoggingViolations = 0;
        $envViolations = 0;

        if (isset($forbiddenPatterns['by_category'])) {
            $sensitiveLoggingViolations = $forbiddenPatterns['by_category']['sensitive_logging'] ?? 0;
            $envViolations = $forbiddenPatterns['by_category']['forbidden_env_usage'] ?? 0;
        }

        $this->report['security_hardening'] = [
            'status' => ($sensitiveLoggingViolations === 0 && $envViolations === 0) ? 'VERIFIED_COMPLETE' : 'PARTIALLY_IMPLEMENTED',
            'sensitive_logging_violations' => $sensitiveLoggingViolations,
            'env_usage_violations' => $envViolations,
            'ci_detection_implemented' => File::exists(app_path('Console/Commands/Architecture/DetectForbiddenPatterns.php')),
            'log_redaction_enabled' => config('observability.log_redaction.enabled', false),
            'redaction_keys_count' => count(config('observability.log_redaction.sensitive_keys', [])),
        ];
    }

    private function assessFeatureFlagGovernance(): void
    {
        $this->info('Assessing P2: Feature Flag Governance...');

        $flagErrors = FeatureFlag::validateAll();
        $allFlags = FeatureFlag::all();

        $this->report['feature_flag_governance'] = [
            'status' => empty($flagErrors) ? 'VERIFIED_COMPLETE' : 'PARTIALLY_IMPLEMENTED',
            'total_flags' => count($allFlags),
            'valid_flags' => count($allFlags) - count($flagErrors),
            'invalid_flags' => count($flagErrors),
            'registry_exists' => File::exists(config_path('features.php')),
            'helper_exists' => class_exists(\App\Support\FeatureFlags\FeatureFlag::class),
            'validation_command_exists' => File::exists(app_path('Console/Commands/Architecture/ValidateFeatureFlags.php')),
            'by_category' => $this->groupByCategory($allFlags),
            'by_wave' => $this->groupByWave($allFlags),
            'kill_switches_count' => count(FeatureFlag::killSwitches()),
        ];
    }

    private function assessForbiddenPatterns(): void
    {
        $this->info('Assessing P3: CI Enforcement...');

        $ciWorkflowExists = File::exists(base_path('.github/workflows/wave1-governance.yml'));
        
        Artisan::call('architecture:detect-forbidden-patterns', ['--json' => true]);
        $forbiddenPatterns = json_decode(Artisan::output(), true);

        $this->report['ci_enforcement'] = [
            'status' => $ciWorkflowExists ? 'VERIFIED_COMPLETE' : 'PARTIALLY_IMPLEMENTED',
            'github_workflow_exists' => $ciWorkflowExists,
            'forbidden_pattern_detection_exists' => true,
            'drift_detection_exists' => File::exists(app_path('Console/Commands/Architecture/DetectAuthorizationDrift.php')),
            'total_violations' => $forbiddenPatterns['total_violations'] ?? 0,
            'by_category' => $forbiddenPatterns['by_category'] ?? [],
        ];
    }

    private function assessDriftDetection(): void
    {
        $this->info('Assessing Architecture Drift Detection...');

        $driftCommandExists = File::exists(app_path('Console/Commands/Architecture/DetectAuthorizationDrift.php'));

        $this->report['drift_detection'] = [
            'status' => $driftCommandExists ? 'VERIFIED_COMPLETE' : 'NEEDS_MANUAL_REVIEW',
            'command_exists' => $driftCommandExists,
            'baseline_exists' => File::exists(storage_path('app/testing/architecture-drift-baseline.json')),
            'allowlist_exists' => File::exists(base_path('docs/wave2/drift-allowlist.json')),
        ];
    }

    private function assessOperationalFoundations(): void
    {
        $this->info('Assessing P4: Operational Foundations...');

        $dashboardsExist = File::exists(base_path('docs/dashboards'));
        $alertsExist = File::exists(base_path('docs/alerts'));

        $this->report['operational_foundations'] = [
            'status' => ($dashboardsExist && $alertsExist) ? 'VERIFIED_COMPLETE' : 'PARTIALLY_IMPLEMENTED',
            'dashboards_defined' => $dashboardsExist,
            'alerts_defined' => $alertsExist,
            'observability_config_exists' => File::exists(config_path('observability.php')),
            'correlation_enabled' => config('observability.correlation_header') !== null,
            'security_log_channel_exists' => config('observability.security_log_channel') !== null,
        ];
    }

    private function assessQueueObservability(): void
    {
        $this->info('Assessing P5: Queue Observability...');

        $queueTelemetryExists = File::exists(app_path('Support/Queue'));

        $this->report['queue_observability'] = [
            'status' => $queueTelemetryExists ? 'PARTIALLY_IMPLEMENTED' : 'NEEDS_MANUAL_REVIEW',
            'queue_telemetry_infrastructure' => $queueTelemetryExists,
            'correlation_continuity_ready' => config('observability.correlation_header') !== null,
        ];
    }

    private function assessGovernanceDocumentation(): void
    {
        $this->info('Assessing P7: Governance Documentation...');

        $adrDir = base_path('docs/adr');
        $runbooksDir = base_path('docs/runbooks');

        $this->report['governance_documentation'] = [
            'status' => (File::isDirectory($adrDir) && File::isDirectory($runbooksDir)) ? 'PARTIALLY_IMPLEMENTED' : 'NEEDS_MANUAL_REVIEW',
            'adr_directory_exists' => File::isDirectory($adrDir),
            'runbooks_directory_exists' => File::isDirectory($runbooksDir),
            'architecture_md_exists' => File::exists(base_path('docs/ARCHITECTURE.md')),
            'execution_governance_exists' => File::exists(base_path('docs/EXECUTION_GOVERNANCE.md')),
            'observability_docs_exists' => File::exists(base_path('docs/OBSERVABILITY.md')),
        ];
    }

    private function determineGateStatus(): void
    {
        $blockers = [];

        if ($this->report['security_hardening']['status'] !== 'VERIFIED_COMPLETE') {
            $blockers[] = 'Critical security hardening incomplete';
        }

        if ($this->report['feature_flag_governance']['status'] !== 'VERIFIED_COMPLETE') {
            $blockers[] = 'Feature flag governance incomplete';
        }

        if ($this->report['ci_enforcement']['status'] !== 'VERIFIED_COMPLETE') {
            $blockers[] = 'CI enforcement not fully implemented';
        }

        $status = empty($blockers) ? 'READY' : 'BLOCKED';

        $this->report['wave1_gate'] = [
            'status' => $status,
            'blocked_by' => $blockers,
            'readiness_score' => $this->calculateReadinessScore(),
        ];
    }

    private function calculateReadinessScore(): float
    {
        $components = [
            'security_hardening',
            'feature_flag_governance',
            'ci_enforcement',
            'drift_detection',
            'operational_foundations',
            'queue_observability',
            'governance_documentation',
        ];

        $completed = 0;
        foreach ($components as $component) {
            if (($this->report[$component]['status'] ?? '') === 'VERIFIED_COMPLETE') {
                $completed++;
            }
        }

        return round(($completed / count($components)) * 100, 2);
    }

    private function saveReport(): void
    {
        $this->report['generated_at'] = now()->toIso8601String();
        $this->report['release_version'] = config('observability.release_version', 'dev');

        $outputPath = storage_path('app/testing/wave1-readiness-report.json');
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, json_encode($this->report, JSON_PRETTY_PRINT));

        $this->info("Report saved to: {$outputPath}");
    }

    private function displayHumanReadable(): void
    {
        $this->newLine();
        $this->info('=== WAVE 1 READINESS REPORT ===');
        $this->newLine();

        $this->displayComponentStatus('P1: Security Hardening', $this->report['security_hardening']);
        $this->displayComponentStatus('P2: Feature Flag Governance', $this->report['feature_flag_governance']);
        $this->displayComponentStatus('P3: CI Enforcement', $this->report['ci_enforcement']);
        $this->displayComponentStatus('Drift Detection', $this->report['drift_detection']);
        $this->displayComponentStatus('P4: Operational Foundations', $this->report['operational_foundations']);
        $this->displayComponentStatus('P5: Queue Observability', $this->report['queue_observability']);
        $this->displayComponentStatus('P7: Governance Documentation', $this->report['governance_documentation']);

        $this->newLine();
        $this->info('=== WAVE 1 GATE STATUS ===');
        $gateStatus = $this->report['wave1_gate']['status'];
        $readinessScore = $this->report['wave1_gate']['readiness_score'];

        if ($gateStatus === 'READY') {
            $this->info("✓ READY — Readiness Score: {$readinessScore}%");
        } else {
            $this->error("✗ BLOCKED — Readiness Score: {$readinessScore}%");
            $this->newLine();
            $this->warn('Blockers:');
            foreach ($this->report['wave1_gate']['blocked_by'] as $blocker) {
                $this->line("  - {$blocker}");
            }
        }
    }

    private function displayComponentStatus(string $name, array $data): void
    {
        $status = $data['status'] ?? 'UNKNOWN';
        $icon = match($status) {
            'VERIFIED_COMPLETE' => '✓',
            'PARTIALLY_IMPLEMENTED' => '◐',
            'NEEDS_MANUAL_REVIEW' => '○',
            default => '?',
        };

        $this->line("{$icon} {$name}: {$status}");
    }

    private function groupByCategory(array $flags): array
    {
        $grouped = [];
        foreach ($flags as $config) {
            if (is_array($config)) {
                $category = $config['category'] ?? 'uncategorized';
                $grouped[$category] = ($grouped[$category] ?? 0) + 1;
            }
        }
        return $grouped;
    }

    private function groupByWave(array $flags): array
    {
        $grouped = [];
        foreach ($flags as $config) {
            if (is_array($config)) {
                $wave = $config['introduced_wave'] ?? 'unknown';
                $grouped[$wave] = ($grouped[$wave] ?? 0) + 1;
            }
        }
        return $grouped;
    }
}
