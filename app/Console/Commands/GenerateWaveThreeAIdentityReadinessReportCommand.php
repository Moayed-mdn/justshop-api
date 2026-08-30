<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Auth\Readiness\WaveThreeAIdentityReadinessReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateWaveThreeAIdentityReadinessReportCommand extends Command
{
    protected $signature = 'architecture:wave3a-readiness-report {--output=}';

    protected $description = 'Generate a machine-readable Wave 3A identity normalization readiness artifact';

    public function handle(WaveThreeAIdentityReadinessReportService $reportService): int
    {
        $report = $reportService->generate();
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error('Failed to encode Wave 3A readiness report.');

            return self::FAILURE;
        }

        $outputPath = $this->option('output') ?: storage_path('app/wave3a-readiness-report.json');

        if (is_string($outputPath) && $outputPath !== '') {
            File::ensureDirectoryExists(dirname($outputPath));
            File::put($outputPath, $json);
            $this->info("Wave 3A readiness report written to {$outputPath}");
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }
}
