<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Domain\Shared\Events\StoreCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Auto-add Store to /etc/hosts in Local Development
 * 
 * When a store is created via the dashboard, automatically add it to /etc/hosts
 * so developers don't have to manually run the helper script.
 * 
 * ⚠️ IMPORTANT: This ONLY runs in local development environment.
 * Production environments use wildcard DNS and MUST NOT modify /etc/hosts.
 * 
 * Multiple safeguards ensure this never runs in production:
 * 1. Environment check (local only)
 * 2. Config flag check (APP_AUTO_UPDATE_HOSTS)
 * 3. Domain check (.test, .local, .localhost only)
 * 4. Queue check (shouldQueue returns false in production)
 */
class AutoAddStoreToHostsFile implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(StoreCreated $event): void
    {
        // ⛔ SAFEGUARD 1: Only run in local environment
        if (!app()->environment('local')) {
            Log::channel('store')->debug('🛡️ SAFEGUARD #1: Skipping /etc/hosts update (not local environment)', [
                'safeguard' => 'environment_check',
                'environment' => app()->environment(),
                'subdomain' => $event->slug,
                'reason' => 'Production environments must use wildcard DNS',
            ]);
            return;
        }

        // ⛔ SAFEGUARD 2: Check config flag
        if (!config('app.auto_update_hosts', true)) {
            Log::channel('store')->debug('🛡️ SAFEGUARD #2: Skipping /etc/hosts update (disabled in config)', [
                'safeguard' => 'config_flag_check',
                'subdomain' => $event->slug,
                'config_value' => config('app.auto_update_hosts'),
            ]);
            return;
        }

        // ⛔ SAFEGUARD 3: Only update for local development domains
        $domainSuffix = config('app.domain', 'justshop.test');
        $allowedTLDs = ['test', 'local', 'localhost'];
        $tld = substr($domainSuffix, strrpos($domainSuffix, '.') + 1);
        
        if (!in_array($tld, $allowedTLDs, true)) {
            Log::channel('store')->warning('🛡️ SAFEGUARD #3: Refusing to update /etc/hosts for production-like domain', [
                'safeguard' => 'domain_tld_check',
                'domain_suffix' => $domainSuffix,
                'tld' => $tld,
                'allowed_tlds' => $allowedTLDs,
                'subdomain' => $event->slug,
                'reason' => 'Production domains (.com, .net, etc.) must use wildcard DNS, not /etc/hosts',
            ]);
            return;
        }

        
        $subdomain = $event->slug;
        $fullDomain = "{$subdomain}.{$domainSuffix}";
        
        Log::channel('store')->info('Attempting to add store to /etc/hosts', [
            'subdomain' => $subdomain,
            'full_domain' => $fullDomain,
        ]);

        try {
            $this->addToHostsFile($fullDomain);
            
            Log::channel('store')->info('✅ Store added to /etc/hosts', [
                'domain' => $fullDomain,
                'store_id' => $event->storeId,
            ]);
        } catch (\Exception $e) {
            // Don't fail store creation if hosts file update fails
            Log::channel('store')->warning('Failed to add store to /etc/hosts', [
                'domain' => $fullDomain,
                'error' => $e->getMessage(),
                'hint' => 'Run: ./scripts/add-store-to-hosts.sh ' . $subdomain,
            ]);
        }
    }

    /**
     * Add domain to /etc/hosts file
     */
    private function addToHostsFile(string $fullDomain): void
    {
        $hostsFile = '/etc/hosts';
        $ip = '127.0.0.1';
        
        // Check if already exists
        $hostsContent = file_get_contents($hostsFile);
        
        if ($hostsContent === false) {
            throw new \RuntimeException("Cannot read {$hostsFile}");
        }
        
        if (str_contains($hostsContent, $fullDomain)) {
            Log::channel('store')->debug('Domain already exists in /etc/hosts', [
                'domain' => $fullDomain,
            ]);
            return;
        }

        // Add to hosts file using sudo
        $entry = "{$ip} {$fullDomain}";
        $scriptPath = base_path('scripts/add-store-to-hosts.sh');
        
        // Get just the subdomain (remove domain suffix)
        $subdomain = str_replace('.' . config('app.domain', 'justshop.test'), '', $fullDomain);
        
        // Execute the helper script
        if (file_exists($scriptPath)) {
            $command = "bash {$scriptPath} {$subdomain} 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \RuntimeException(
                    "Failed to execute hosts script. Output: " . implode("\n", $output)
                );
            }
        } else {
            // Fallback: direct echo to hosts file
            $command = "echo '{$entry}' | sudo tee -a {$hostsFile} > /dev/null 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \RuntimeException("Failed to add entry to {$hostsFile}");
            }
        }
    }

    /**
     * Determine whether the listener should be queued.
     * 
     * ⛔ SAFEGUARD 4: Never queue in production
     */
    public function shouldQueue(StoreCreated $event): bool
    {
        // Only queue in local environment to avoid blocking the response
        // In production, this listener won't even execute (see handle() checks)
        return app()->environment('local');
    }
}
