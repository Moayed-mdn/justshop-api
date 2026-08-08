<?php

namespace App\Console\Commands\Billing;

use App\Models\BillingAccount;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ReconcileEntitlementCountsCommand
 * 
 * Recalculates and fixes:
 * - products_count in store_entitlement_snapshots (store-level)
 * - stores_count in billing_accounts (account-level)
 * 
 * This is a self-healing safety net for:
 * - Initial backfill after migration
 * - Bulk operations that bypass observers
 * - Race conditions (though atomic updates minimize this)
 * - Manual database modifications
 * - Unknown future bugs
 * 
 * Usage:
 *   php artisan entitlements:reconcile
 *   php artisan entitlements:reconcile --dry-run
 */
class ReconcileEntitlementCountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'entitlements:reconcile
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Reconcile products_count (snapshots) and stores_count (billing_accounts) with actual database records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 Running in DRY-RUN mode (no changes will be made)');
        } else {
            $this->info('🔄 Reconciling entitlement counts...');
        }

        // Reconcile products_count (store-level in snapshots)
        $this->info("\n📦 Reconciling products_count...");
        $productsStats = $this->reconcileProductsCount($isDryRun);

        // Reconcile stores_count (account-level in billing_accounts)
        $this->info("\n🏪 Reconciling stores_count...");
        $storesStats = $this->reconcileStoresCount($isDryRun);

        // Summary
        $this->newLine();
        $this->info("📊 Reconciliation Summary:");
        $this->table(
            ['Metric', 'Products Count', 'Stores Count'],
            [
                ['Records Checked', $productsStats['total'], $storesStats['total']],
                ['Drifts Detected', $productsStats['drift'], $storesStats['drift']],
                ['Drifts Fixed', 
                    $isDryRun ? '0 (dry-run)' : $productsStats['fixed'],
                    $isDryRun ? '0 (dry-run)' : $storesStats['fixed']
                ],
            ]
        );

        $totalDrift = $productsStats['drift'] + $storesStats['drift'];
        
        if ($totalDrift > 0) {
            if ($isDryRun) {
                $this->warn("⚠️  Run without --dry-run to fix the drifts");
            } else {
                $this->warn("⚠️  Drifts detected and fixed. Investigate the cause:");
                $this->line("   - Check logs for 'Entitlement count drift' warnings");
                $this->line("   - Look for bulk operations bypassing observers");
                $this->line("   - Review recent code changes to Product/Store models");
            }
            return self::FAILURE;
        }

        $this->info("✅ All entitlement counts are accurate!");
        return self::SUCCESS;
    }

    /**
     * Reconcile products_count in store_entitlement_snapshots.
     */
    private function reconcileProductsCount(bool $isDryRun): array
    {
        $totalSnapshots = 0;
        $driftCount = 0;
        $fixedCount = 0;

        StoreEntitlementSnapshot::query()->chunkById(200, function ($snapshots) use (&$totalSnapshots, &$driftCount, &$fixedCount, $isDryRun) {
            foreach ($snapshots as $snapshot) {
                $totalSnapshots++;
                
                // Calculate actual count from source of truth
                $actualProductsCount = Product::where('store_id', $snapshot->store_id)->count();
                
                if ($actualProductsCount !== $snapshot->products_count) {
                    $driftCount++;
                    
                    $drift = $actualProductsCount - $snapshot->products_count;
                    
                    Log::warning('Products count drift detected', [
                        'snapshot_id' => $snapshot->id,
                        'store_id' => $snapshot->store_id,
                        'stored' => $snapshot->products_count,
                        'actual' => $actualProductsCount,
                        'drift' => $drift,
                    ]);
                    
                    $this->warn(sprintf(
                        '  Snapshot #%d (Store #%d): %d → %d (drift: %+d)',
                        $snapshot->id,
                        $snapshot->store_id,
                        $snapshot->products_count,
                        $actualProductsCount,
                        $drift
                    ));

                    if (!$isDryRun) {
                        $snapshot->update(['products_count' => $actualProductsCount]);
                        $fixedCount++;
                    }
                }
            }
        });

        return [
            'total' => $totalSnapshots,
            'drift' => $driftCount,
            'fixed' => $fixedCount,
        ];
    }

    /**
     * Reconcile stores_count in billing_accounts.
     */
    private function reconcileStoresCount(bool $isDryRun): array
    {
        $totalAccounts = 0;
        $driftCount = 0;
        $fixedCount = 0;

        BillingAccount::query()->chunkById(100, function ($accounts) use (&$totalAccounts, &$driftCount, &$fixedCount, $isDryRun) {
            foreach ($accounts as $account) {
                $totalAccounts++;
                
                // Calculate actual count from source of truth
                $actualStoresCount = Store::where('owner_id', $account->owner_user_id)->count();
                
                if ($actualStoresCount !== $account->stores_count) {
                    $driftCount++;
                    
                    $drift = $actualStoresCount - $account->stores_count;
                    
                    Log::warning('Stores count drift detected', [
                        'billing_account_id' => $account->id,
                        'owner_user_id' => $account->owner_user_id,
                        'stored' => $account->stores_count,
                        'actual' => $actualStoresCount,
                        'drift' => $drift,
                    ]);
                    
                    $this->warn(sprintf(
                        '  Account #%d (Owner #%d): %d → %d (drift: %+d)',
                        $account->id,
                        $account->owner_user_id,
                        $account->stores_count,
                        $actualStoresCount,
                        $drift
                    ));

                    if (!$isDryRun) {
                        $account->update(['stores_count' => $actualStoresCount]);
                        $fixedCount++;
                    }
                }
            }
        });

        return [
            'total' => $totalAccounts,
            'drift' => $driftCount,
            'fixed' => $fixedCount,
        ];
    }
}
