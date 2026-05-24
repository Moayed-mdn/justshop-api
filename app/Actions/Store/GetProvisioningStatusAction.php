<?php

declare(strict_types=1);

namespace App\Actions\Store;

use App\DTOs\Store\ProvisioningStatusDTO;
use App\Models\Store;

class GetProvisioningStatusAction
{
    public function __construct(
        private readonly \App\Services\Store\ProvisioningHealthService $provisioningHealthService,
    ) {}

    public function execute(Store $store): ProvisioningStatusDTO
    {
        $store = $this->provisioningHealthService->refresh($store);

        return new ProvisioningStatusDTO(
            status: $store->provisioning_status,
            progress: $store->provisioning_progress,
            currentStep: $store->provisioning_current_step,
            message: $store->provisioning_message,
            retryable: (bool) $store->provisioning_retryable,
        );
    }
}
