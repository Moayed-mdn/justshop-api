<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Services\Governance\PolicyGovernanceEnforcer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PolicyGovernanceReportCommand extends Command
{
    protected $signature = 'architecture:policy-governance-report {--output=policy-governance-report.json}';
    protected $description = 'Enforce policy registry validation and detect governance violations';

    public function handle(PolicyGovernanceEnforcer $enforcer): int
    {
        $this->info('Starting Policy Governance Audit...');

        $report = $enforcer->generateReport();

        $outputFile = $this->option('output');
        File::put(base_path($outputFile), json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Report generated: {$outputFile}");

        if ($report['policy_registry_drift']) {
            $this->error('CRITICAL: Policy registry drift detected!');
            foreach ($report['unregistered_policies'] as $policy) {
                $this->line(" - Unregistered: {$policy}");
            }
        }

        if (count($report['actor_blind_policies']) > 0) {
            $this->warn('WARNING: Actor-blind policies detected:');
            foreach ($report['actor_blind_policies'] as $policy) {
                $this->line(" - Blind: {$policy}");
            }
        }

        if (count($report['escalation_capable_policies']) > 0) {
            $this->warn('WARNING: Escalation-capable policies detected:');
            foreach ($report['escalation_capable_policies'] as $policy) {
                $this->line(" - Escalation risk: {$policy}");
            }
        }

        return 0;
    }
}
