<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Auth\Readiness\WaveTwoOperationalReadinessReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateWaveTwoOperationalReadinessReportCommand extends Command
{
    protected $signature = 'architecture:wave2-readiness-report {--output=} {--allowlist=} {--baseline=}';

    protected $description = 'Generate a machine-readable Wave 2 operational readiness artifact';

    public function handle(WaveTwoOperationalReadinessReportService $reportService): int
    {
        $report = $reportService->generate(
            allowlistPath: $this->option('allowlist') ?: null,
            baselinePath: $this->option('baseline') ?: null,
        );

        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error('Failed to encode Wave 2 readiness report.');

            return self::FAILURE;
        }

        $outputPath = $this->option('output') ?: config('migration.drift_detection.readiness_report_path');

        if (is_string($outputPath) && $outputPath !== '') {
            File::ensureDirectoryExists(dirname($outputPath));
            File::put($outputPath, $json);
            $this->info("Wave 2 readiness report written to {$outputPath}");
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }
}
