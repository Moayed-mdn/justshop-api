<?php

declare(strict_types=1);

namespace App\Actions\Store;

use App\Enums\Store\ProvisioningStatusEnum;
use App\Jobs\Store\BootstrapStoreJob;
use App\Models\Store;
use App\Services\Store\ProvisioningHealthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RetryProvisioningAction
{
    public function __construct(
        private readonly ProvisioningHealthService $provisioningHealthService,
    ) {}

    public function execute(Store $store): Store
    {
        $store = $this->provisioningHealthService->refresh($store);

        if ($store->provisioning_status !== ProvisioningStatusEnum::FAILED) {
            throw ValidationException::withMessages([
                'store' => 'Provisioning is not in a retryable failed state.',
            ]);
        }

        if (!$store->provisioning_retryable) {
            throw ValidationException::withMessages([
                'store' => 'Provisioning retry is not available for this store.',
            ]);
        }

        $store = DB::transaction(function () use ($store): Store {
            $prepared = $this->provisioningHealthService->prepareRetry($store);

            DB::afterCommit(function () use ($prepared): void {
                BootstrapStoreJob::dispatch($prepared->id);
            });

            return $prepared;
        });

        return $store;
    }
}
