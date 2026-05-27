<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\ListAddressesRequest;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Services\AddressService;
use App\DTOs\Address\StoreAddressDTO;
use App\DTOs\Address\UpdateAddressDTO;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function __construct(
        private AddressService $addressService,
    ) {}

    public function index(ListAddressesRequest $request, int $store)
    {
        $addresses = $this->addressService->getUserAddresses(
            $store,
            $request->user()->id,
            $request->input('type')
        );

        return $this->success(
            AddressResource::collection($addresses),
            'Addresses retrieved successfully'
        );
    }

    public function store(StoreAddressRequest $request, int $store)
    {
        $address = $this->addressService->storeAddress(
            $store,
            StoreAddressDTO::fromRequest($request, $store)
        );

        return $this->success(new AddressResource($address), __('general.address_added'), 201);
    }

    public function update(UpdateAddressRequest $request, int $store, Address $address)
    {
        $this->authorize('update', $address);
        
        $updated = $this->addressService->updateAddress(
            $address,
            UpdateAddressDTO::fromRequest($request, $store)
        );

        return $this->success(new AddressResource($updated), __('general.address_updated'));
    }

    public function destroy(Request $request, int $store, Address $address)
    {
        $this->authorize('delete', $address);
  
        $this->addressService->deleteAddress($address, $store);
  
        return $this->success(null, __('general.address_deleted'));
    }

    public function setDefault(Request $request, int $store, Address $address)
    {
        $this->authorize('update', $address);
  
        $this->addressService->setAsDefault($address, $store);
  
        return $this->success(null, __('general.address_set_default'));
    }
}