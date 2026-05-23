<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Services\Enterprise\MembershipLifecycleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EnterpriseMembershipGovernanceReportCommand extends Command
{
    protected $signature = 'architecture:enterprise-membership-governance-report {--output=enterprise-membership-governance-report.json}';
    protected $description = 'Generate enterprise membership governance report';

    public function handle(MembershipLifecycleManager $manager): int
    {
        $this->info('Generating Enterprise Membership Governance Report...');

        $report = $manager->generateGovernanceReport();

        $outputFile = $this->option('output');
        File::put(base_path($outputFile), json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Report generated: {$outputFile}");

        if (count($report['stale_active_stores']) > 0) {
            $this->warn('WARNING: Stale active stores detected!');
        }

        if (count($report['orphaned_memberships']) > 0) {
            $this->warn('WARNING: Orphaned memberships detected!');
        }

        return 0;
    }
}
