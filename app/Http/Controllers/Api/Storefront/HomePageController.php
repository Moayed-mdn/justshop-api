<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Actions\Product\GetBestSellersAction;
use App\DTOs\Product\GetBestSellersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomePage\GetBestSellersRequest;
use App\Http\Resources\BestSellerResource;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class HomePageController extends Controller
{
    public function __construct(
        private GetBestSellersAction $getBestSellersAction,
    ) {}

    public function bestSeller(GetBestSellersRequest $request, Store $store): JsonResponse
    {
        $dtos = $this->getBestSellersAction->execute(
            GetBestSellersDTO::fromRequest($request, $store->id)
        );

        return $this->success(BestSellerResource::collection($dtos));
    }
}
