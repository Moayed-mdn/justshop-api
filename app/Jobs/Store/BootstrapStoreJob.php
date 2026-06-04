<?php

declare(strict_types=1);

namespace App\Jobs\Store;

use App\Enums\Store\ProvisioningStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Enums\Auth\OnboardingStepEnum;
use App\Models\Store;
use App\Services\Auth\OnboardingTransitionService;
use App\Services\Store\StoreInitializationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * BootstrapStoreJob
 *
 * Runs after a store is created to initialize default settings,
 * activate the store, and seed any required default data.
 *
 * Design rules:
 * - Idempotent: StoreInitializationService guards against double-runs.
 * - Retryable: up to 3 attempts with exponential backoff.
 * - Queue: 'store-bootstrap' (dedicated queue for store lifecycle jobs).
 * - Timeout: 60 seconds (initialization should be fast).
 */
class BootstrapStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly int $storeId,
    ) {
        $this->onQueue('store-bootstrap');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("store-bootstrap:{$this->storeId}"))->releaseAfter(30),
        ];
    }

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(
        StoreInitializationService $initializationService,
        OnboardingTransitionService $onboardingTransitionService
    ): void
    {
        Log::info('Job: BootstrapStoreJob starting', [
            'store_id' => $this->storeId,
        ]);

        $store = Store::find($this->storeId);

        if (!$store) {
            Log::warning('BootstrapStoreJob: store not found, skipping.', [
                'store_id' => $this->storeId,
            ]);
            return;
        }

        if ($store->setup_completed_at !== null) {
            Log::info('Job: BootstrapStoreJob - Store already bootstrapped, skipping.', [
                'store_id' => $this->storeId,
            ]);
            $this->markCompleted($store, $onboardingTransitionService);

            return;
        }

        $this->markRunning($store);
        
        try {
            $initializationService->initialize($store);
            $store->refresh();
            $this->markCompleted($store, $onboardingTransitionService);

            Log::info('Job: BootstrapStoreJob completed successfully', [
                'store_id' => $this->storeId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Job: BootstrapStoreJob encountered an error', [
                'store_id' => $this->storeId,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $store = Store::find($this->storeId);

        if ($store) {
            $retryable = (int) $store->provisioning_attempts < (int) config('lifecycle.provisioning.max_retry_attempts', 5);

            $store->update([
                'provisioning_status' => ProvisioningStatusEnum::FAILED,
                'provisioning_retryable' => $retryable,
                'provisioning_current_step' => 'bootstrap_failed',
                'provisioning_message' => $retryable
                    ? 'Store provisioning failed. Retry provisioning to continue setup.'
                    : 'Store provisioning failed. Please contact support if the issue persists.',
                'provisioning_last_heartbeat_at' => now(),
                'provisioning_failed_at' => now(),
                'provisioning_last_error' => $exception->getMessage(),
            ]);
        }

        Log::error('BootstrapStoreJob: failed after all retries.', [
            'store_id'  => $this->storeId,
            'exception' => $exception->getMessage(),
        ]);
    }

    private function markRunning(Store $store): void
    {
        $updates = [
            'provisioning_status' => ProvisioningStatusEnum::RUNNING,
            'provisioning_retryable' => false,
            'provisioning_current_step' => 'initializing_store',
            'provisioning_message' => 'Initializing store resources.',
            'provisioning_last_heartbeat_at' => now(),
            'provisioning_failed_at' => null,
            'provisioning_completed_at' => null,
            'provisioning_last_error' => null,
            'provisioning_attempts' => ((int) $store->provisioning_attempts) + 1,
        ];

        if (($store->provisioning_progress ?? 0) < 10) {
            $updates['provisioning_progress'] = 10;
        }

        if ($store->provisioning_started_at === null) {
            $updates['provisioning_started_at'] = now();
        }

        if ($store->status === StoreStatusEnum::PENDING_SETUP) {
            $updates['status'] = StoreStatusEnum::PROVISIONING;
            $updates['is_active'] = false;
            $updates['status_changed_at'] = now();
        }

        $store->update($updates);
    }

    private function markCompleted(Store $store, OnboardingTransitionService $onboardingTransitionService): void
    {
        $store->update([
            'provisioning_status' => ProvisioningStatusEnum::COMPLETED,
            'provisioning_progress' => 100,
            'provisioning_retryable' => false,
            'provisioning_current_step' => null,
            'provisioning_message' => null,
            'provisioning_last_heartbeat_at' => now(),
            'provisioning_completed_at' => now(),
            'provisioning_failed_at' => null,
            'provisioning_last_error' => null,
        ]);

        // If this was part of the onboarding flow, mark onboarding as completed.
        $owner = $store->owner;
        if ($owner && $owner->onboarding_step !== OnboardingStepEnum::COMPLETED) {
            if ($owner->onboarding_step !== null && $owner->onboarding_step->canTransitionTo(OnboardingStepEnum::COMPLETED)) {
                $onboardingTransitionService->transition($owner, OnboardingStepEnum::COMPLETED);
            } else {
                // The owner's step is in an unexpected state (e.g. pending_verification) —
                // use forceSet so the store bootstrap never fails on an onboarding edge-case.
                // This mirrors the recovery logic in OnboardingRecoveryService::recover().
                $onboardingTransitionService->forceSet(
                    $owner,
                    OnboardingStepEnum::COMPLETED,
                    'bootstrap_store_job_recovery',
                );
            }

            Log::info('BootstrapStoreJob: onboarding marked as completed for store owner', [
                'store_id' => $store->id,
                'owner_id' => $owner->id,
                'previous_step' => $owner->onboarding_step?->value,
            ]);
        }
    }
}
