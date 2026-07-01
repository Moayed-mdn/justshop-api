<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Actions\Cart\AddToCartAction;
use App\Actions\Cart\BulkAddToCartAction;
use App\Actions\Cart\ClearCartAction;
use App\Actions\Cart\GetCartAction;
use App\Actions\Cart\RemoveCartItemAction;
use App\Actions\Cart\UpdateCartItemAction;
use App\DTOs\Cart\AddToCartDTO;
use App\DTOs\Cart\BulkAddToCartDTO;
use App\DTOs\Cart\ClearCartDTO;
use App\DTOs\Cart\GetCartDTO;
use App\DTOs\Cart\RemoveCartItemDTO;
use App\DTOs\Cart\UpdateCartItemDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddItemRequest;
use App\Http\Requests\Cart\ClearRequest;
use App\Http\Requests\Cart\RemoveItemRequest;
use App\Http\Requests\Cart\UpdateItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private GetCartAction $getCartAction,
        private AddToCartAction $addToCartAction,
        private BulkAddToCartAction $bulkAddToCartAction,
        private UpdateCartItemAction $updateCartItemAction,
        private RemoveCartItemAction $removeCartItemAction,
        private ClearCartAction $clearCartAction,
    ) {}

    public function show(
        Request $request, 
        Store $store,
    ): JsonResponse {
        $cart = $this->getCartAction->execute(
            GetCartDTO::fromRequest($request, $store->id)
        );

        return $this->success(new CartResource($cart));
    }

    public function addItem(AddItemRequest $request, Store $store): JsonResponse
    {
        $cart = $this->addToCartAction->execute(
            AddToCartDTO::fromRequest($request, $store->id)
        );

        return $this->success(new CartResource($cart));
    }

    public function bulkAdd(Request $request, Store $store): JsonResponse
    {
        $cart = $this->bulkAddToCartAction->execute(
            BulkAddToCartDTO::fromRequest($request, $store->id)
        );

        return $this->success(new CartResource($cart));
    }

    public function updateItem(UpdateItemRequest $request, Store $store): JsonResponse
    {
        $this->updateCartItemAction->execute(
            UpdateCartItemDTO::fromRequest($request, $store->id)
        );

        return $this->show($request, $store);
    }

    public function removeItem(RemoveItemRequest $request, Store $store): JsonResponse
    {
        $this->removeCartItemAction->execute(
            RemoveCartItemDTO::fromRequest($request, $store->id)
        );

        return $this->show($request, $store);
    }

    public function clear(ClearRequest $request, Store $store): JsonResponse
    {
        $this->clearCartAction->execute(
            ClearCartDTO::fromRequest($request, $store->id)
        );

        return $this->success(null, __('cart.cleared'));
    }
}