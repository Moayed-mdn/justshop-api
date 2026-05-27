<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Actions\Product\GetProductDetailAction;
use App\Actions\Category\GetProductsByCategoryAction;
use App\Actions\Product\GetRelatedProductsAction;
use App\Actions\Product\ListProductsAction;
use App\DTOs\Product\GetProductDetailDTO;
use App\DTOs\Category\GetProductsByCategoryDTO;
use App\DTOs\Product\GetRelatedProductsDTO;
use App\DTOs\Product\ListProductsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\FilterProductsRequest;
use App\Http\Requests\Product\GetProductDetailRequest;
use App\Http\Requests\Product\GetProductsByCategoryRequest;
use App\Http\Requests\Product\GetRelatedProductsRequest;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\RelatedProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ListProductsAction $listProductsAction,
        private GetProductsByCategoryAction $getProductsByCategoryAction,
        private GetProductDetailAction $getProductDetailAction,
        private GetRelatedProductsAction $getRelatedProductsAction,
    ) {}

    public function index(FilterProductsRequest $request, int $store): JsonResponse
    {
        $result = $this->listProductsAction->execute(
            ListProductsDTO::fromRequest($request, $store)
        );

        $paginator = $result['paginator'];
        $variantStatus = $result['variant_status'];

        return $this->paginated(
            $paginator,
            ProductCardResource::collection($paginator->items()),
            [
                'filters' => [
                    'descendants' => $result['descendants'],
                    'min_price' => $variantStatus->min_price,
                    'max_price' => $variantStatus->max_price,
                    'earliest_manufacture' => $variantStatus->earliest_manufacture,
                    'latest_expiry' => $variantStatus->latest_expiry
                ]
            ]
        );
    }

    public function indexByCategory(GetProductsByCategoryRequest $request, int $store, string $slug): JsonResponse
    {
        $result = $this->getProductsByCategoryAction->execute(
            GetProductsByCategoryDTO::fromRequest($request, $store, $slug)
        );

        $paginator = $result['paginator'];
        $variantStatus = $result['variant_status'];

        return $this->paginated(
            $paginator,
            ProductCardResource::collection($paginator->items()),
            [
                'category' => $result['category'],
                'filters' => [
                    'descendants'          => $result['descendant_categories'],
                    'min_price'            => $variantStatus->min_price ?? null,
                    'max_price'            => $variantStatus->max_price ?? null,
                    'earliest_manufacture' => $variantStatus->earliest_manufacture ?? null,
                    'latest_expiry'        => $variantStatus->latest_expiry ?? null,
                ],
            ]
        );
    }

    public function show(GetProductDetailRequest $request, int $store, string $slug): JsonResponse
    {
        $product = $this->getProductDetailAction->execute(
            GetProductDetailDTO::fromRequest($request, $store, $slug)
        );

        return $this->success(new ProductDetailResource($product));
    }

    public function related(GetRelatedProductsRequest $request, int $store, string $slug): JsonResponse
    {
        $relatedProducts = $this->getRelatedProductsAction->execute(
            GetRelatedProductsDTO::fromRequest($request, $store, $slug)
        );

        return $this->success(RelatedProductResource::collection($relatedProducts));
    }
}
