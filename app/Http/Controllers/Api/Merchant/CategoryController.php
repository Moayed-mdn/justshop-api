<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Category\GetCategoriesAction;
use App\Actions\Category\GetCategoryAction;
use App\Actions\Category\GetCategoryBreadcrumbAction;
use App\DTOs\Category\GetCategoriesDTO;
use App\DTOs\Category\GetCategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\GetCategoriesRequest;
use App\Http\Requests\Category\GetCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private GetCategoriesAction $getCategoriesAction,
        private GetCategoryAction $getCategoryAction,
        private GetCategoryBreadcrumbAction $getCategoryBreadcrumbAction,
    ) {}

    public function index(GetCategoriesRequest $request): JsonResponse
    {
        $categories = $this->getCategoriesAction->execute(
            GetCategoriesDTO::fromRequest($request)
        );

        return $this->success(new CategoryCollection($categories));
    }

    public function show(GetCategoryRequest $request): JsonResponse
    {
        $category = $this->getCategoryAction->execute(
            GetCategoryDTO::fromRequest($request)
        );

        return $this->success(new CategoryResource($category));
    }

    public function breadcrumb(GetCategoryRequest $request): JsonResponse
    {
        $breadcrumb = $this->getCategoryBreadcrumbAction->execute(
            GetCategoryDTO::fromRequest($request)
        );

        return $this->success($breadcrumb);
    }
}
