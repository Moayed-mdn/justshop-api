<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Services\Auth\MultiSessionGovernanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SessionCoexistenceReportCommand extends Command
{
    protected $signature = 'architecture:session-coexistence-report {--output=session-coexistence-report.json}';
    protected $description = 'Generate session coexistence and multi-session governance report';

    public function handle(MultiSessionGovernanceService $service): int
    {
        $this->info('Generating Session Coexistence Report...');

        $report = $service->generateCoexistenceReport();

        $outputFile = $this->option('output');
        File::put(base_path($outputFile), json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Report generated: {$outputFile}");

        return 0;
    }
}
