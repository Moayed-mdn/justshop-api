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
use Illuminate\Http\JsonResponse;

class AdminProductController extends Controller
{
    public function index(ListProductsRequest $request, ListProductsAction $action, Store $store): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('viewAny', $storeModel);
        
        $products = $action->execute(ListProductsDTO::fromRequest($request, $store->id));
        return $this->paginated($products, AdminProductResource::collection($products));
    }

    public function show(GetProductRequest $request, GetProductAction $action, Store $store, int $product): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('view', $storeModel);
        
        $productModel = $action->execute(GetProductDTO::fromRequest($request, $store->id, $product));
        return $this->success(new AdminProductDetailResource($productModel));
    }

    public function store(CreateProductRequest $request, CreateProductAction $action, Store $store): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('create', $storeModel);
        
        $product = $action->execute(CreateProductDTO::fromRequest($request, $store->id));
        return $this->success(new AdminProductDetailResource($product), __('admin.product_created'));
    }

    public function update(UpdateProductRequest $request, UpdateProductAction $action, Store $store, int $product): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('update', $storeModel);
        
        $productModel = $action->execute(UpdateProductDTO::fromRequest($request, $store->id, $product));
        return $this->success(new AdminProductDetailResource($productModel), __('admin.product_updated'));
    }

    public function destroy(DeleteProductRequest $request, DeleteProductAction $action, Store $store, int $product): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('delete', $storeModel);
        
        $action->execute(DeleteProductDTO::fromRequest($request, $store->id, $product));
        return $this->success(null, __('admin.product_deleted'));
    }

    public function restore(RestoreProductRequest $request, RestoreProductAction $action, Store $store, int $product): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('update', $storeModel);
        
        $productModel = $action->execute(RestoreProductDTO::fromRequest($request, $store->id, $product));
        return $this->success(new AdminProductDetailResource($productModel), __('admin.product_restored'));
    }
}
