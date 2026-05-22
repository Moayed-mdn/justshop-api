<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Auth\Drift\AuthorizationDriftReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DetectAuthorizationDriftCommand extends Command
{
    protected $signature = 'architecture:detect-authorization-drift
        {--format=console : console or json}
        {--output= : Optional file path for the machine-readable report}
        {--allowlist= : Optional allowlist path}
        {--baseline= : Optional baseline path for regression comparison}
        {--write-baseline : Write the generated report to the configured baseline path}';

    protected $description = 'Warn about hidden or drifting authorization paths without failing CI';

    public function handle(AuthorizationDriftReportService $reportService): int
    {
        if (!config('migration.drift_detection.enabled', true)) {
            $this->info('Authorization drift detection is disabled.');

            return self::SUCCESS;
        }

        $report = $reportService->generate(
            allowlistPath: $this->option('allowlist') ?: null,
            baselinePath: $this->option('baseline') ?: null,
        );

        $this->writeReportArtifact($report);

        if ($this->option('write-baseline')) {
            $baselinePath = (string) config('migration.drift_detection.baseline_path');
            File::ensureDirectoryExists(dirname($baselinePath));
            File::put($baselinePath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info("Baseline snapshot written to {$baselinePath}");
        }

        if ($this->option('format') === 'json') {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $grouped = [];
        foreach ($report['active_findings'] as $finding) {
            $grouped[$finding['category']][] = $finding;
        }

        foreach ($grouped as $category => $findings) {
            $this->newLine();
            $this->line("<comment>{$category}</comment> (" . count($findings) . ")");

            foreach ($findings as $finding) {
                $this->line(sprintf(
                    '  - [%s] %s:%d %s',
                    strtoupper((string) $finding['severity']),
                    $finding['file'],
                    $finding['line'],
                    $finding['message'],
                ));
            }
        }

        if (($report['active_findings'] ?? []) === []) {
            $this->newLine();
            $this->line('  - none');
        }

        $this->newLine();
        $this->info('Authorization drift detection completed in warning mode. Total warnings: ' . ($report['summary']['active_findings'] ?? 0) . '.');
        $this->line('Trend: new=' . ($report['trend']['new_since_baseline'] ?? 0) . ', resolved=' . ($report['trend']['resolved_since_baseline'] ?? 0) . '.');

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function writeReportArtifact(array $report): void
    {
        $outputPath = $this->option('output') ?: config('migration.drift_detection.report_path');
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (!is_string($outputPath) || $outputPath === '' || $json === false) {
            return;
        }

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $json);
    }
}
