<?php

namespace App\Actions\Shipping;

use App\DTOs\Shipping\UpdateStoreAddressSettingsDTO;
use App\Models\Store;
use App\Models\StoreAddressSetting;
use App\Services\StoreAddressSettingsService;

/**
 * Update store address settings (or create if not exists).
 */
class UpdateStoreAddressSettingsAction
{
    public function __construct(
        private StoreAddressSettingsService $storeAddressSettingsService,
    ) {}

    public function execute(Store $store, UpdateStoreAddressSettingsDTO $dto): StoreAddressSetting
    {
        return $this->storeAddressSettingsService->updateSettings($store, $dto->toArray());
    }
}
