<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Admin\Brand\CreateBrandAction;
use App\Actions\Admin\Brand\DeleteBrandAction;
use App\Actions\Admin\Brand\ListBrandsAction;
use App\Actions\Admin\Brand\RestoreBrandAction;
use App\Actions\Admin\Brand\ShowBrandAction;
use App\Actions\Admin\Brand\UpdateBrandAction;
use App\DTOs\Admin\Brand\CreateBrandDTO;
use App\DTOs\Admin\Brand\DeleteBrandDTO;
use App\DTOs\Admin\Brand\ListBrandsDTO;
use App\DTOs\Admin\Brand\RestoreBrandDTO;
use App\DTOs\Admin\Brand\ShowBrandDTO;
use App\DTOs\Admin\Brand\UpdateBrandDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Brand\CreateBrandRequest;
use App\Http\Requests\Admin\Brand\ListBrandsRequest;
use App\Http\Requests\Admin\Brand\UpdateBrandRequest;
use App\Http\Resources\Admin\Brand\AdminBrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

class AdminBrandController extends Controller
{
    public function index(
        ListBrandsRequest $request,
        int $store,
        ListBrandsAction $action,
    ): JsonResponse {
        $this->authorize('viewAny', [Brand::class, $this->currentStore()]);

        $brands = $action->execute(
            dto:  ListBrandsDTO::fromRequest($request, $store),
        );

        return $this->paginated(
            $brands,
            AdminBrandResource::collection($brands),
        );
    }

    public function show(
        int $store,
        int $brand,
        ShowBrandAction $action,
    ): JsonResponse {
        $this->authorize('view', [Brand::class, $this->currentStore()]);

        $result = $action->execute(
            dto: new ShowBrandDTO(
                storeId: $store,
                brandId: $brand,
            ),
        );

        return $this->success(new AdminBrandResource($result));
    }

    public function store(
        CreateBrandRequest $request,
        int $store,
        CreateBrandAction $action,
    ): JsonResponse {
        $this->authorize('create', [Brand::class, $this->currentStore()]);

        $result = $action->execute(
            dto:  CreateBrandDTO::fromRequest($request, $store),
        );

        return $this->success(
            data:       new AdminBrandResource($result),
            message:    __( 'brand.created'),
            statusCode: 201,
        );
    }

    public function update(
        UpdateBrandRequest $request,
        int $store,
        int $brand,
        UpdateBrandAction $action,
    ): JsonResponse {
        $this->authorize('update', [Brand::class, $this->currentStore()]);

        $result = $action->execute(
            dto:  UpdateBrandDTO::fromRequest($request, $store, $brand),
        );

        return $this->success(
            data:    new AdminBrandResource($result),
            message: __( 'brand.updated'),
        );
    }

    public function destroy(
        int $store,
        int $brand,
        DeleteBrandAction $action,
    ): JsonResponse {
        $this->authorize('delete', [Brand::class, $this->currentStore()]);

        $action->execute(
            dto: new DeleteBrandDTO(
                storeId: $store,
                brandId: $brand,
            ),
        );

        return $this->success(
            message: __( 'brand.deleted'),
        );
    }

    public function restore(
        int $store,
        int $brand,
        RestoreBrandAction $action,
    ): JsonResponse {
        $this->authorize('restore', [Brand::class, $this->currentStore()]);

        $result = $action->execute(
            dto: new RestoreBrandDTO(
                storeId: $store,
                brandId: $brand,
            ),
        );

        return $this->success(
            data:    new AdminBrandResource($result),
            message: __( 'brand.restored'),
        );
    }
}
