<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\ListAddressesRequest;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Requests\Address\ValidateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\Shipping\StoreAddressSettingResource;
use App\Services\AddressService;
use App\Actions\Address\ValidateAddressAction;
use App\DTOs\Address\StoreAddressDTO;
use App\DTOs\Address\UpdateAddressDTO;
use App\DTOs\Address\ValidateAddressDTO;
use App\Models\Address;
use App\Models\Store;
use App\Services\StoreAddressSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function __construct(
        private AddressService $addressService,
        private ValidateAddressAction $validateAddressAction,
        private StoreAddressSettingsService $storeAddressSettingsService,
    ) {}

    public function index(ListAddressesRequest $request, Store $store)
    {
        $addresses = $this->addressService->getUserAddresses(
            $store->id,
            $request->user()->id,
            $request->input('type')
        );

        return $this->success(
            AddressResource::collection($addresses),
            'Addresses retrieved successfully'
        );
    }

    public function store(StoreAddressRequest $request, Store $store)
    {
        $address = $this->addressService->storeAddress(
            $store->id,
            StoreAddressDTO::fromRequest($request, $store->id)
        );

        return $this->success(new AddressResource($address), __('general.address_added'), 201);
    }

    public function update(UpdateAddressRequest $request, Store $store, Address $address)
    {
        $this->authorize('update', $address);
        
        $updated = $this->addressService->updateAddress(
            $address,
            UpdateAddressDTO::fromRequest($request, $store->id)
        );

        return $this->success(new AddressResource($updated), __('general.address_updated'));
    }

    public function destroy(Request $request, Store $store, Address $address)
    {
        $this->authorize('delete', $address);
  
        $this->addressService->deleteAddress($address, $store->id);
  
        return $this->success(null, __('general.address_deleted'));
    }

    public function setDefault(Request $request, Store $store, Address $address)
    {
        $this->authorize('update', $address);
  
        $this->addressService->setAsDefault($address, $store->id);
  
        return $this->success(null, __('general.address_set_default'));
    }

    public function validate(ValidateAddressRequest $request, Store $store)
    {
        $result = $this->validateAddressAction->execute(
            ValidateAddressDTO::fromArray($request->validated(), $store->id)
        );

        return $this->success($result, 'Address validation complete');
    }

    /**
     * Set address as default shipping address.
     */
    public function setDefaultShipping(Request $request, Store $store, Address $address): JsonResponse
    {
        $this->authorize('update', $address);
        
        $address->setAsDefaultShipping();
        
        return $this->success(new AddressResource($address), 'Default shipping address set');
    }

    /**
     * Set address as default billing address.
     */
    public function setDefaultBilling(Request $request, Store $store, Address $address): JsonResponse
    {
        $this->authorize('update', $address);
        
        $address->setAsDefaultBilling();
        
        return $this->success(new AddressResource($address), 'Default billing address set');
    }

    /**
     * Get allowed countries for the store.
     */
    public function getAllowedCountries(Request $request, Store $store): JsonResponse
    {
        $allowedCountries = $this->storeAddressSettingsService->getAvailableCountries($store);
        
        return $this->success([
            'allowed_countries' => $allowedCountries
        ], 'Allowed countries retrieved successfully');
    }

    /**
     * Get storefront address settings for the active store.
     */
    public function getSettings(Request $request, Store $store): JsonResponse
    {
        $settings = $this->storeAddressSettingsService->getSettings($store);

        return $this->success(
            new StoreAddressSettingResource($settings),
            'Address settings retrieved successfully'
        );
    }
}
