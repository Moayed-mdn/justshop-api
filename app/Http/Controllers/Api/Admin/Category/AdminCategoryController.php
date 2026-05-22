<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Category;

use App\Actions\Admin\Category\CreateCategoryAction;
use App\Actions\Admin\Category\DeleteCategoryAction;
use App\Actions\Admin\Category\ListCategoriesAction;
use App\Actions\Admin\Category\RestoreCategoryAction;
use App\Actions\Admin\Category\ShowCategoryAction;
use App\Actions\Admin\Category\UpdateCategoryAction;
use App\DTOs\Admin\Category\CreateCategoryDTO;
use App\DTOs\Admin\Category\DeleteCategoryDTO;
use App\DTOs\Admin\Category\ListCategoriesDTO;
use App\DTOs\Admin\Category\RestoreCategoryDTO;
use App\DTOs\Admin\Category\ShowCategoryDTO;
use App\DTOs\Admin\Category\UpdateCategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\CreateCategoryRequest;
use App\Http\Requests\Admin\Category\ListCategoriesRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Http\Resources\Admin\Category\AdminCategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class AdminCategoryController extends Controller
{
    public function index(
        ListCategoriesRequest $request,
        int $store,
        ListCategoriesAction $action,
    ): JsonResponse {
        $this->authorize('viewAny', [Category::class, $this->currentStore()]);

        $categories = $action->execute(
            dto:  ListCategoriesDTO::fromRequest($request, $store),
        );

        return $this->paginated(
            $categories,
            AdminCategoryResource::collection($categories),
        );
    }

    public function show(
        int $store,
        int $category,
        ShowCategoryAction $action,
    ): JsonResponse {
        $this->authorize('view', [Category::class, $this->currentStore()]);

        $result = $action->execute(
            dto: new ShowCategoryDTO(
                storeId:    $store,
                categoryId: $category,
            ),
        );

        return $this->success(new AdminCategoryResource($result));
    }

    public function store(
        CreateCategoryRequest $request,
        int $store,
        CreateCategoryAction $action,
    ): JsonResponse {
        $this->authorize('create', [Category::class, $this->currentStore()]);

        $result = $action->execute(
            dto:  CreateCategoryDTO::fromRequest($request, $store),
        );

        return $this->success(
            data:       new AdminCategoryResource($result),
            message:    __( 'category.created'),
            statusCode: 201,
        );
    }

    public function update(
        UpdateCategoryRequest $request,
        int $store,
        int $category,
        UpdateCategoryAction $action,
    ): JsonResponse {
        $this->authorize('update', [Category::class, $this->currentStore()]);

        $result = $action->execute(
            dto:  UpdateCategoryDTO::fromRequest($request, $store, $category),
        );

        return $this->success(
            data:    new AdminCategoryResource($result),
            message: __( 'category.updated'),
        );
    }

    public function destroy(
        int $store,
        int $category,
        DeleteCategoryAction $action,
    ): JsonResponse {
        $this->authorize('delete', [Category::class, $this->currentStore()]);

        $action->execute(
            dto: new DeleteCategoryDTO(
                storeId:    $store,
                categoryId: $category,
            ),
        );

        return $this->success(
            message: __( 'category.deleted'),
        );
    }

    public function restore(
        int $store,
        int $category,
        RestoreCategoryAction $action,
    ): JsonResponse {
        $this->authorize('restore', [Category::class, $this->currentStore()]);

        $result = $action->execute(
            dto: new RestoreCategoryDTO(
                storeId:    $store,
                categoryId: $category,
            ),
        );

        return $this->success(
            data:    new AdminCategoryResource($result),
            message: __( 'category.restored'),
        );
    }
}
