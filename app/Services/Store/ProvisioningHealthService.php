<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Enums\Store\ProvisioningStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\Log;

class ProvisioningHealthService
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    public function refresh(Store $store): Store
    {
        if (!in_array($store->provisioning_status, [ProvisioningStatusEnum::PENDING, ProvisioningStatusEnum::RUNNING], true)) {
            return $store;
        }

        $cutoff = now()->subMinutes((int) config('lifecycle.provisioning.stale_after_minutes', 10));
        $startedAt = $store->provisioning_started_at;
        $heartbeatAt = $store->provisioning_last_heartbeat_at ?? $startedAt;

        if ($startedAt === null || $heartbeatAt === null) {
            return $store;
        }

        if ($heartbeatAt->gt($cutoff)) {
            return $store;
        }

        $store->update([
            'status' => $store->status === StoreStatusEnum::ACTIVE ? StoreStatusEnum::ACTIVE : StoreStatusEnum::PROVISIONING,
            'is_active' => false,
            'provisioning_status' => ProvisioningStatusEnum::FAILED,
            'provisioning_retryable' => true,
            'provisioning_current_step' => 'bootstrap_timed_out',
            'provisioning_message' => 'Store provisioning timed out. Retry provisioning to continue setup.',
            'provisioning_failed_at' => now(),
            'provisioning_last_error' => 'Provisioning heartbeat exceeded timeout window.',
        ]);

        $metadata = [
            'store_id' => (int) $store->id,
            'provisioning_status' => ProvisioningStatusEnum::FAILED->value,
            'timeout_minutes' => (int) config('lifecycle.provisioning.stale_after_minutes', 10),
        ];

        $this->auditLogger->record('store.provisioning.timed_out', $metadata);
        Log::warning('store.provisioning.timed_out', $metadata);

        return $store->fresh();
    }

    public function prepareRetry(Store $store): Store
    {
        $store->update([
            'status' => StoreStatusEnum::PENDING_SETUP,
            'is_active' => false,
            'setup_completed_at' => null,
            'provisioning_status' => ProvisioningStatusEnum::PENDING,
            'provisioning_progress' => 0,
            'provisioning_current_step' => null,
            'provisioning_message' => null,
            'provisioning_retryable' => false,
            'provisioning_started_at' => null,
            'provisioning_last_heartbeat_at' => null,
            'provisioning_completed_at' => null,
            'provisioning_failed_at' => null,
            'provisioning_last_error' => null,
            'status_changed_at' => now(),
        ]);

        $this->auditLogger->record('store.provisioning.retry_prepared', [
            'store_id' => (int) $store->id,
            'attempts' => (int) $store->provisioning_attempts,
        ]);

        return $store->fresh();
    }
}
