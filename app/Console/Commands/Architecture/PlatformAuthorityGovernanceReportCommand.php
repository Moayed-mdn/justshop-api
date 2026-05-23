<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Services\Platform\PlatformAuthorityGovernanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PlatformAuthorityGovernanceReportCommand extends Command
{
    protected $signature = 'architecture:platform-authority-governance-report {--output=platform-authority-governance-report.json}';
    protected $description = 'Generate platform and support authority governance report';

    public function handle(PlatformAuthorityGovernanceService $service): int
    {
        $this->info('Generating Platform Authority Governance Report...');

        $report = $service->generateGovernanceReport();

        $outputFile = $this->option('output');
        File::put(base_path($outputFile), json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Report generated: {$outputFile}");

        return 0;
    }
}
