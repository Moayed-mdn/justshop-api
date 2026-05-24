<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StoreInitializationService
 *
 * Bootstraps a newly created store with default configuration.
 *
 * Design rules:
 * - Idempotent: safe to call multiple times on the same store.
 * - Retry-safe: each step checks if it has already been applied.
 * - Transactional: all initialization steps run in a single transaction.
 * - Queue-ready: designed to be called from a Job (see BootstrapStoreJob).
 * - No business logic: only default data seeding.
 */
class StoreInitializationService
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Initialize a store with production defaults.
     * Safe to call multiple times — already-initialized stores are skipped.
     */
    public function initialize(Store $store): void
    {
        // Idempotency guard: if setup_completed_at is already set, skip.
        if ($store->setup_completed_at !== null) {
            Log::info('store.initialization.skipped_already_complete', [
                'store_id' => $store->id,
            ]);
            return;
        }

        Log::info('store.initialization.started', ['store_id' => $store->id]);

        DB::transaction(function () use ($store): void {
            $this->applyDefaultSettings($store);
            $this->activateStore($store);

            $store->update(['setup_completed_at' => now()]);

            $this->auditLogger->record('store.initialized', [
                'store_id' => $store->id,
                'slug'     => $store->slug,
            ]);
        });

        Log::info('store.initialization.completed', ['store_id' => $store->id]);
    }

    /**
     * Apply default store settings.
     * Extend this method as the platform grows (default categories, policies, etc.)
     */
    private function applyDefaultSettings(Store $store): void
    {
        $defaults = [];

        // Apply currency default if not already set.
        if (empty($store->currency)) {
            $defaults['currency'] = 'USD';
        }

        // Apply timezone default if not already set.
        if (empty($store->timezone)) {
            $defaults['timezone'] = 'UTC';
        }

        if (!empty($defaults)) {
            $store->update($defaults);
        }
    }

    /**
     * Transition store status from pending_setup → active.
     * Idempotent: already-active stores are not touched.
     */
    private function activateStore(Store $store): void
    {
        if ($store->status === StoreStatusEnum::ACTIVE) {
            return;
        }

        if (!in_array($store->status, [StoreStatusEnum::PENDING_SETUP, StoreStatusEnum::PROVISIONING], true)) {
            Log::warning('store.initialization.unexpected_status', [
                'store_id' => $store->id,
                'status'   => $store->status?->value,
            ]);
            return;
        }

        $store->update([
            'status'    => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);
    }
}
