<?php

namespace App\Http\Controllers\Api\Admin\Product;

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
use Illuminate\Http\JsonResponse;

class AdminProductController extends Controller
{
    public function index(ListProductsRequest $request, ListProductsAction $action, int $store): JsonResponse
    {
        $this->authorize('view', app('currentStore'));
        $products = $action->execute(ListProductsDTO::fromRequest($request, $store));
        return $this->paginated($products, AdminProductResource::collection($products));
    }

    public function show(GetProductRequest $request, GetProductAction $action, int $store, int $product): JsonResponse
    {
        $this->authorize('view', app('currentStore'));
        $productModel = $action->execute(GetProductDTO::fromRequest($request, $store, $product));
        return $this->success(new AdminProductDetailResource($productModel));
    }

    public function store(CreateProductRequest $request, CreateProductAction $action, int $store): JsonResponse
    {
        $this->authorize('update', app('currentStore'));
        $product = $action->execute(CreateProductDTO::fromRequest($request, $store));
        return $this->success(new AdminProductDetailResource($product), __('admin.product_created'));
    }

    public function update(UpdateProductRequest $request, UpdateProductAction $action, int $store, int $product): JsonResponse
    {
        $this->authorize('update', app('currentStore'));
        $productModel = $action->execute(UpdateProductDTO::fromRequest($request, $store, $product));
        return $this->success(new AdminProductDetailResource($productModel), __('admin.product_updated'));
    }

    public function destroy(DeleteProductRequest $request, DeleteProductAction $action, int $store, int $product): JsonResponse
    {
        $this->authorize('update', app('currentStore'));
        $action->execute(DeleteProductDTO::fromRequest($request, $store, $product));
        return $this->success(null, __('admin.product_deleted'));
    }

    public function restore(RestoreProductRequest $request, RestoreProductAction $action, int $store, int $product): JsonResponse
    {
        $this->authorize('update', app('currentStore'));
        $productModel = $action->execute(RestoreProductDTO::fromRequest($request, $store, $product));
        return $this->success(new AdminProductDetailResource($productModel), __('admin.product_restored'));
    }
}
