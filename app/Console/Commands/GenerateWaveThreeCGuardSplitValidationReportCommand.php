<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Auth\Readiness\WaveThreeCGuardSplitValidationReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateWaveThreeCGuardSplitValidationReportCommand extends Command
{
    protected $signature = 'architecture:wave3c-guard-split-validation-report {--output=}';

    protected $description = 'Generate a machine-readable Wave 3C guard split readiness validation artifact';

    public function handle(WaveThreeCGuardSplitValidationReportService $reportService): int
    {
        $report = $reportService->generate();
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error('Failed to encode Wave 3C validation report.');

            return self::FAILURE;
        }

        $outputPath = $this->option('output') ?: storage_path('app/wave3c-guard-split-validation-report.json');

        if (is_string($outputPath) && $outputPath !== '') {
            File::ensureDirectoryExists(dirname($outputPath));
            File::put($outputPath, $json);
            $this->info("Wave 3C validation report written to {$outputPath}");
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }
}
