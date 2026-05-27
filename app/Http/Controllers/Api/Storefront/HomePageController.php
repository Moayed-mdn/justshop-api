<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Actions\Product\GetBestSellersAction;
use App\Actions\Homepage\GetHeroBannersAction;
use App\DTOs\Product\GetBestSellersDTO;
use App\DTOs\Homepage\GetHeroBannersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomePage\GetBestSellersRequest;
use App\Http\Requests\HomePage\GetHeroBannersRequest;
use App\Http\Resources\BestSellerResource;
use App\Http\Resources\HeroBannerResource;
use Illuminate\Http\JsonResponse;

class HomePageController extends Controller
{
    public function __construct(
        private GetBestSellersAction $getBestSellersAction,
        private GetHeroBannersAction $getHeroBannersAction,
    ) {}

    public function bestSeller(GetBestSellersRequest $request, int $store): JsonResponse
    {
        $dtos = $this->getBestSellersAction->execute(
            GetBestSellersDTO::fromRequest($request, $store)
        );

        return $this->success(BestSellerResource::collection($dtos));
    }

    public function hero(GetHeroBannersRequest $request, int $store): JsonResponse
    {
        $banners = $this->getHeroBannersAction->execute(
            GetHeroBannersDTO::fromRequest($request, $store)
        );

        return $this->success(HeroBannerResource::collection($banners));
    }
}
