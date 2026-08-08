<?php

namespace Database\Seeders;

use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Models\BillingAccount;
use Illuminate\Database\Seeder;

class ReconcileEntitlementsSeeder extends Seeder
{
    /**
     * Reconcile entitlement counters after seeding.
     * 
     * Seeders may create stores before billing accounts exist, or use bulk
     * operations that bypass observers. This ensures all counters are accurate.
     */
    public function run(): void
    {
        $this->command?->info('🔄 Reconciling entitlement counters...');

        $driftsFixed = 0;

        // Step 1: Recompute entitlements to sync stores_max
        BillingAccount::with('entitlementSnapshots')->chunkById(200, function ($accounts) {
            foreach ($accounts as $account) {
                $snapshot = $account->entitlementSnapshots->first();
                
                if (!$snapshot) {
                    continue;
                }

                app(RecomputeEntitlementsAction::class)->execute(
                    new RecomputeEntitlementsDTO(
                        billingAccountId: $account->id,
                        storeId: $snapshot->store_id,
                    )
                );
            }
        });

        // Step 2: Reconcile counters (stores_count, products_count)
        BillingAccount::query()->chunkById(200, function ($accounts) use (&$driftsFixed) {
            foreach ($accounts as $account) {
                // Fix stores_count
                $actualStores = \App\Models\Store::where('owner_id', $account->owner_user_id)->count();
                if ($account->stores_count !== $actualStores) {
                    $account->update(['stores_count' => $actualStores]);
                    $driftsFixed++;
                }

                // Fix products_count in snapshots
                foreach ($account->entitlementSnapshots as $snapshot) {
                    $actualProducts = \App\Models\Product::where('store_id', $snapshot->store_id)->count();
                    if ($snapshot->products_count !== $actualProducts) {
                        $snapshot->update(['products_count' => $actualProducts]);
                        $driftsFixed++;
                    }
                }
            }
        });

        $this->command?->info("✅ Reconciliation complete. Fixed {$driftsFixed} drift(s).");
    }
}
