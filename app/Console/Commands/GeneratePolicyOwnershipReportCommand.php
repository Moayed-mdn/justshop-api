<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Auth\Policy\PolicyOwnershipReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GeneratePolicyOwnershipReportCommand extends Command
{
    protected $signature = 'architecture:report-policy-ownership {--output=}';

    protected $description = 'Generate a machine-readable policy ownership visibility report';

    public function handle(PolicyOwnershipReportService $reportService): int
    {
        $report = $reportService->generate();
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error('Failed to encode policy ownership report.');

            return self::FAILURE;
        }

        $outputPath = $this->option('output') ?: config('migration.drift_detection.policy_ownership_report_path');

        if (is_string($outputPath) && $outputPath !== '') {
            File::ensureDirectoryExists(dirname($outputPath));
            File::put($outputPath, $json);
            $this->info("Policy ownership report written to {$outputPath}");
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }
}
