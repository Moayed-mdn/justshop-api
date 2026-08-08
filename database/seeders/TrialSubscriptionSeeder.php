<?php

namespace Database\Seeders;

use App\Actions\Subscription\StartTrialAction;
use App\DTOs\Subscription\StartTrialDTO;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrialSubscriptionSeeder extends Seeder
{
    public function __construct(
        private StartTrialAction $startTrialAction
    ) {}

    /**
     * Seed trial subscription for merchant-store.
     * Applies Scenario 1: New user creates first store with automatic trial.
     */
    public function run(): void
    {
        // Find the merchant-store and its owner
        $store = Store::where('slug', 'merchant-store')->first();

        if (!$store) {
            $this->command->warn('⚠️  merchant-store not found. Run StoreSeeder first.');
            return;
        }

        /** @var User $owner */
        $owner = $store->owner;

        if (!$owner) {
            $this->command->warn('⚠️  merchant-store has no owner.');
            return;
        }

        // Check if billing account already exists
        if ($owner->billingAccount) {
            $this->command->info('ℹ️  Billing account already exists for merchant@test.com');
            
            if ($owner->billingAccount->activeSubscription) {
                $this->command->warn('⚠️  Active subscription already exists. Skipping.');
                return;
            }
        }

        // Start trial subscription (just like when creating a new store)
        try {
            $subscription = $this->startTrialAction->execute(
                new StartTrialDTO(
                    ownerUserId: $owner->id,
                    storeId: $store->id,
                    planCode: 'starter', // Default starter plan
                )
            );

            $this->command->info('✅ Trial subscription created successfully!');
            $this->command->info('   Store: ' . $store->name . ' (' . $store->slug . ')');
            $this->command->info('   Owner: ' . $owner->name . ' (' . $owner->email . ')');
            $this->command->info('   Plan: Starter');
            $this->command->info('   Trial Period: 14 days');
            $this->command->info('   Trial Ends: ' . $subscription->trial_ends_at->format('Y-m-d H:i:s'));
            $this->command->info('   Subscription ID: ' . $subscription->id);
            $this->command->info('   Status: ' . $subscription->status->value);

        } catch (\App\Exceptions\Subscription\TrialAlreadyUsedException $e) {
            $this->command->warn('⚠️  ' . $e->getMessage());
            $this->command->info('   The billing account has already used its trial.');
            
        } catch (\Exception $e) {
            $this->command->error('❌ Failed to create trial subscription');
            $this->command->error('   Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
