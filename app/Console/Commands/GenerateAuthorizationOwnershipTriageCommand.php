<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Auth\Drift\AuthorizationOwnershipTriageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateAuthorizationOwnershipTriageCommand extends Command
{
    protected $signature = 'architecture:wave2-authorization-triage {--output=} {--allowlist=} {--baseline=}';

    protected $description = 'Generate a machine-readable Wave 2.5 authorization ownership triage report';

    public function handle(AuthorizationOwnershipTriageService $triageService): int
    {
        $report = $triageService->generate(
            allowlistPath: $this->option('allowlist') ?: null,
            baselinePath: $this->option('baseline') ?: null,
        );

        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error('Failed to encode authorization ownership triage report.');

            return self::FAILURE;
        }

        $outputPath = $this->option('output') ?: config('migration.drift_detection.triage_report_path');

        if (is_string($outputPath) && $outputPath !== '') {
            File::ensureDirectoryExists(dirname($outputPath));
            File::put($outputPath, $json);
            $this->info("Authorization ownership triage report written to {$outputPath}");
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }
}
