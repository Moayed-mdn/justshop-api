<?php

namespace App\Listeners\Subscription;

use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Events\Subscription\SubscriptionActivated;
use App\Events\Subscription\SubscriptionStatusChanged;
use App\Events\Subscription\TrialStarted;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RecomputeEntitlementsListener implements ShouldQueue
{
    public function __construct(
        private RecomputeEntitlementsAction $recomputeEntitlementsAction,
    ) {}

    /**
     * Handle TrialStarted event.
     */
    public function handleTrialStarted(TrialStarted $event): void
    {
        $this->recomputeForStore($event->subscription->billing_account_id, $event->storeId);
    }

    /**
     * Handle SubscriptionActivated event.
     */
    public function handleSubscriptionActivated(SubscriptionActivated $event): void
    {
        $this->recomputeForAllStores($event->subscription->billing_account_id);
    }

    /**
     * Handle SubscriptionStatusChanged event.
     */
    public function handleSubscriptionStatusChanged(SubscriptionStatusChanged $event): void
    {
        $this->recomputeForAllStores($event->subscription->billing_account_id);
    }

    /**
     * Recompute entitlements for a specific store.
     */
    private function recomputeForStore(int $billingAccountId, int $storeId): void
    {
        try {
            $this->recomputeEntitlementsAction->execute(
                new RecomputeEntitlementsDTO(
                    billingAccountId: $billingAccountId,
                    storeId: $storeId,
                )
            );
        } catch (\Exception $e) {
            Log::channel('billing')->error('entitlements.recompute.failed', [
                'billing_account_id' => $billingAccountId,
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Recompute entitlements for all stores owned by this billing account.
     */
    private function recomputeForAllStores(int $billingAccountId): void
    {
        // Get billing account to find owner
        $billingAccount = \App\Models\BillingAccount::find($billingAccountId);
        
        if (!$billingAccount) {
            return;
        }

        // Get all stores owned by this user
        $stores = Store::where('owner_id', $billingAccount->owner_user_id)->get();

        foreach ($stores as $store) {
            $this->recomputeForStore($billingAccountId, $store->id);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            TrialStarted::class => 'handleTrialStarted',
            SubscriptionActivated::class => 'handleSubscriptionActivated',
            SubscriptionStatusChanged::class => 'handleSubscriptionStatusChanged',
        ];
    }
}
