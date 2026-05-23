<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Services\Auth\ProviderGovernanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProviderExtractionReadinessReportCommand extends Command
{
    protected $signature = 'architecture:provider-extraction-readiness-report {--output=provider-extraction-readiness-report.json}';
    protected $description = 'Generate provider extraction readiness report';

    public function handle(ProviderGovernanceService $service): int
    {
        $this->info('Generating Provider Extraction Readiness Report...');

        $report = $service->getProviderReadinessReport();

        $outputFile = $this->option('output');
        File::put(base_path($outputFile), json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Report generated: {$outputFile}");

        return 0;
    }
}
