<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Admin\Product\CreateProductAction;
use App\Actions\Admin\Product\DeleteProductAction;
use App\Actions\Admin\Product\GetProductAction;
use App\Actions\Admin\Product\ListProductsAction;
use App\Actions\Admin\Product\RestoreProductAction;
use App\Actions\Admin\Product\UpdateProductAction;
use App\DTOs\Admin\Product\CreateProductDTO;
use App\DTOs\Admin\Product\DeleteProductDTO;
use App\DTOs\Admin\Product\GetProductDTO;
use App\DTOs\Admin\Product\ListProductsDTO;
use App\DTOs\Admin\Product\RestoreProductDTO;
use App\DTOs\Admin\Product\UpdateProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\CreateProductRequest;
use App\Http\Requests\Admin\Product\DeleteProductRequest;
use App\Http\Requests\Admin\Product\GetProductRequest;
use App\Http\Requests\Admin\Product\ListProductsRequest;
use App\Http\Requests\Admin\Product\RestoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\Admin\Product\AdminProductDetailResource;
use App\Http\Resources\Admin\Product\AdminProductResource;
use App\Models\Store;
use App\Policies\ProductPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminProductController extends Controller
{
    public function index(ListProductsRequest $request, ListProductsAction $action, Store $store): JsonResponse
    {
        $this->authorize('viewAny', [ProductPolicy::class, $store]);

        $products = $action->execute(ListProductsDTO::fromRequest($request, $store->id));
        return $this->paginated($products, AdminProductResource::collection($products));
    }

    public function show(GetProductRequest $request, GetProductAction $action, Store $store, int $product): JsonResponse
    {
        $this->authorize('view', [ProductPolicy::class, $store]);

        $productModel = $action->execute(GetProductDTO::fromRequest($request, $store->id, $product));
        return $this->success(new AdminProductDetailResource($productModel));
    }

    public function store(CreateProductRequest $request, CreateProductAction $action, Store $store): JsonResponse
    {
        $this->authorize('create', [ProductPolicy::class, $store]);

        $product = $action->execute(CreateProductDTO::fromRequest($request, $store->id));
        return $this->success(new AdminProductDetailResource($product), __('admin.product_created'));
    }

    public function update(UpdateProductRequest $request, UpdateProductAction $action, Store $store, int $product): JsonResponse
    {   Log::info('here',['store'=>$store,'product'=>$product]);
        $this->authorize('update', [ProductPolicy::class, $store]);

        $productModel = $action->execute(UpdateProductDTO::fromRequest($request, $store->id, $product));
        return $this->success(new AdminProductDetailResource($productModel), __('admin.product_updated'));
    }

    public function destroy(DeleteProductRequest $request, DeleteProductAction $action, Store $store, int $product): JsonResponse
    {
        $this->authorize('delete', [ProductPolicy::class, $store]);

        $action->execute(DeleteProductDTO::fromRequest($request, $store->id, $product));
        return $this->success(null, __('admin.product_deleted'));
    }

    public function restore(RestoreProductRequest $request, RestoreProductAction $action, Store $store, int $product): JsonResponse
    {
        $this->authorize('restore', [ProductPolicy::class, $store]);

        $productModel = $action->execute(RestoreProductDTO::fromRequest($request, $store->id, $product));
        return $this->success(new AdminProductDetailResource($productModel), __('admin.product_restored'));
    }
}
