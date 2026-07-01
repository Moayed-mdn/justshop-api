<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Shipping\AssignMethodToZoneAction;
use App\Actions\Shipping\CreateShippingMethodAction;
use App\Actions\Shipping\CreateShippingZoneAction;
use App\Actions\Shipping\DeleteShippingMethodAction;
use App\Actions\Shipping\DeleteShippingZoneAction;
use App\Actions\Shipping\RemoveMethodFromZoneAction;
use App\Actions\Shipping\UpdateShippingMethodAction;
use App\Actions\Shipping\UpdateShippingZoneAction;
use App\Actions\Shipping\UpdateStoreAddressSettingsAction;
use App\Actions\Shipping\UpdateZoneMethodPriceAction;
use App\DTOs\Shipping\AssignMethodToZoneDTO;
use App\DTOs\Shipping\CreateShippingMethodDTO;
use App\DTOs\Shipping\CreateShippingZoneDTO;
use App\DTOs\Shipping\UpdateShippingMethodDTO;
use App\DTOs\Shipping\UpdateShippingZoneDTO;
use App\DTOs\Shipping\UpdateStoreAddressSettingsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shipping\AssignMethodToZoneRequest;
use App\Http\Requests\Shipping\StoreShippingMethodRequest;
use App\Http\Requests\Shipping\StoreShippingZoneRequest;
use App\Http\Requests\Shipping\UpdateShippingMethodRequest;
use App\Http\Requests\Shipping\UpdateShippingZoneRequest;
use App\Http\Requests\Shipping\UpdateStoreAddressSettingsRequest;
use App\Http\Requests\Shipping\UpdateZoneMethodPriceRequest;
use App\Http\Resources\Shipping\ShippingMethodResource;
use App\Http\Resources\Shipping\ShippingZoneResource;
use App\Http\Resources\Shipping\StoreAddressSettingResource;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\StoreAddressSettingsService;
use Illuminate\Http\JsonResponse;

class ShippingController extends Controller
{
    public function __construct(
        private CreateShippingMethodAction $createMethodAction,
        private UpdateShippingMethodAction $updateMethodAction,
        private DeleteShippingMethodAction $deleteMethodAction,
        private CreateShippingZoneAction $createZoneAction,
        private UpdateShippingZoneAction $updateZoneAction,
        private DeleteShippingZoneAction $deleteZoneAction,
        private AssignMethodToZoneAction $assignMethodToZoneAction,
        private RemoveMethodFromZoneAction $removeMethodFromZoneAction,
        private UpdateZoneMethodPriceAction $updateZoneMethodPriceAction,
        private UpdateStoreAddressSettingsAction $updateAddressSettingsAction,
        private StoreAddressSettingsService $storeAddressSettingsService,
    ) {
    }

    // ============ STORE ADDRESS SETTINGS ============

    /**
     * Get store address settings.
     */
    public function getAddressSettings(Store $store): JsonResponse
    {
        $settings = $this->storeAddressSettingsService->getSettings($store);

        return $this->success(new StoreAddressSettingResource($settings));
    }

    /**
     * Update store address settings.
     */
    public function updateAddressSettings(
        UpdateStoreAddressSettingsRequest $request,
        Store $store
    ): JsonResponse {
        $dto = UpdateStoreAddressSettingsDTO::fromArray($request->validated(), $store->id);
        $settings = $this->updateAddressSettingsAction->execute($store, $dto);

        return $this->success(
            new StoreAddressSettingResource($settings),
            'Address settings updated successfully.'
        );
    }

    // ============ SHIPPING ZONES ============

    /**
     * List all shipping zones for a store.
     */
    public function listZones(Store $store): JsonResponse
    {
        $zones = $store->shippingZones()->with('methods')->get();

        return $this->success(ShippingZoneResource::collection($zones));
    }

    /**
     * Create a new shipping zone.
     */
    public function createZone(StoreShippingZoneRequest $request, Store $store): JsonResponse
    {
        $dto = CreateShippingZoneDTO::fromArray($request->validated(), $store->id);
        $zone = $this->createZoneAction->execute($dto);

        return $this->success(
            new ShippingZoneResource($zone),
            'Shipping zone created successfully.',
            201
        );
    }

    /**
     * Update a shipping zone.
     */
    public function updateZone(
        UpdateShippingZoneRequest $request,
        Store $store,
        ShippingZone $zone
    ): JsonResponse {
        // Ensure zone belongs to this store
        if ($zone->store_id !== $store->id) {
            return $this->error('Zone not found.', 404);
        }

        $dto = UpdateShippingZoneDTO::fromArray($request->validated(), $store->id);
        $zone = $this->updateZoneAction->execute($zone, $dto);

        return $this->success(
            new ShippingZoneResource($zone->load('methods')),
            'Shipping zone updated successfully.'
        );
    }

    /**
     * Delete a shipping zone.
     */
    public function deleteZone(Store $store, ShippingZone $zone): JsonResponse
    {
        // Ensure zone belongs to this store
        if ($zone->store_id !== $store->id) {
            return $this->error('Zone not found.', 404);
        }

        $this->deleteZoneAction->execute($zone);

        return $this->success(null, 'Shipping zone deleted successfully.');
    }

    // ============ SHIPPING METHODS ============

    /**
     * List all shipping methods for a store.
     */
    public function listMethods(Store $store): JsonResponse
    {
        $methods = $store->shippingMethods()->with('zones')->ordered()->get();

        return $this->success(ShippingMethodResource::collection($methods));
    }

    /**
     * Create a new shipping method.
     */
    public function createMethod(StoreShippingMethodRequest $request, Store $store): JsonResponse
    {
        $dto = CreateShippingMethodDTO::fromArray($request->validated(), $store->id);
        $method = $this->createMethodAction->execute($dto);

        return $this->success(
            new ShippingMethodResource($method),
            'Shipping method created successfully.',
            201
        );
    }

    /**
     * Update a shipping method.
     */
    public function updateMethod(
        UpdateShippingMethodRequest $request,
        Store $store,
        ShippingMethod $method
    ): JsonResponse {
        // Ensure method belongs to this store
        if ($method->store_id !== $store->id) {
            return $this->error('Shipping method not found.', 404);
        }

        $dto = UpdateShippingMethodDTO::fromArray($request->validated(), $store->id);
        $method = $this->updateMethodAction->execute($method, $dto);

        return $this->success(
            new ShippingMethodResource($method->load('zones')),
            'Shipping method updated successfully.'
        );
    }

    /**
     * Delete a shipping method.
     */
    public function deleteMethod(Store $store, ShippingMethod $method): JsonResponse
    {
        // Ensure method belongs to this store
        if ($method->store_id !== $store->id) {
            return $this->error('Shipping method not found.', 404);
        }

        // Check if method is in use by any orders
        if ($method->orders()->exists()) {
            return $this->error(
                'Cannot delete shipping method that has been used in orders. Consider deactivating it instead.',
                422
            );
        }

        $this->deleteMethodAction->execute($method);

        return $this->success(null, 'Shipping method deleted successfully.');
    }

    // ============ ZONE-METHOD ASSIGNMENT ============

    /**
     * Assign a shipping method to a zone with optional price override.
     */
    public function assignMethodToZone(
        AssignMethodToZoneRequest $request,
        Store $store,
        ShippingZone $zone
    ): JsonResponse {
        // Ensure zone belongs to this store
        if ($zone->store_id !== $store->id) {
            return $this->error('Zone not found.', 404);
        }

        $dto = AssignMethodToZoneDTO::fromArray($request->validated(), $store->id, $zone->id);
        
        // Verify method belongs to this store
        $method = ShippingMethod::where('id', $dto->methodId)
            ->where('store_id', $store->id)
            ->firstOrFail();

        $this->assignMethodToZoneAction->execute($dto);

        return $this->success(
            new ShippingZoneResource($zone->load('methods')),
            'Shipping method assigned to zone successfully.'
        );
    }

    /**
     * Remove a shipping method from a zone.
     */
    public function removeMethodFromZone(
        Store $store,
        ShippingZone $zone,
        ShippingMethod $method
    ): JsonResponse {
        // Ensure zone and method belong to this store
        if ($zone->store_id !== $store->id || $method->store_id !== $store->id) {
            return $this->error('Resource not found.', 404);
        }

        $this->removeMethodFromZoneAction->execute($zone, $method);

        return $this->success(null, 'Shipping method removed from zone successfully.');
    }

    /**
     * Update the price override for a method in a specific zone.
     */
    public function updateZoneMethodPrice(
        UpdateZoneMethodPriceRequest $request,
        Store $store,
        ShippingZone $zone,
        ShippingMethod $method
    ): JsonResponse {
        // Ensure zone and method belong to this store
        if ($zone->store_id !== $store->id || $method->store_id !== $store->id) {
            return $this->error('Resource not found.', 404);
        }

        // Ensure method is assigned to this zone
        if (!$zone->methods()->where('shipping_method_id', $method->id)->exists()) {
            return $this->error('Shipping method is not assigned to this zone.', 422);
        }

        $priceOverride = $request->validated()['price_override'] ?? null;
        $this->updateZoneMethodPriceAction->execute($zone, $method, $priceOverride);

        return $this->success(
            new ShippingZoneResource($zone->load('methods')),
            'Zone-specific pricing updated successfully.'
        );
    }
}
