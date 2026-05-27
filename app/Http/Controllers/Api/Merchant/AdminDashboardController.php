<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Admin\Dashboard\GetStatsAction;
use App\Actions\Admin\Dashboard\GetRecentOrdersAction;
use App\Actions\Admin\Dashboard\GetTopProductsAction;
use App\DTOs\Admin\Dashboard\GetStatsDTO;
use App\DTOs\Admin\Dashboard\GetRecentOrdersDTO;
use App\DTOs\Admin\Dashboard\GetTopProductsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dashboard\GetStatsRequest;
use App\Http\Requests\Admin\Dashboard\GetRecentOrdersRequest;
use App\Http\Requests\Admin\Dashboard\GetTopProductsRequest;
use App\Http\Resources\Admin\Dashboard\StoreStatsResource;
use App\Http\Resources\Admin\Dashboard\RecentOrderResource;
use App\Http\Resources\Admin\Dashboard\TopProductResource;
use App\Support\Auth\DashboardAuthorization;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function stats(GetStatsRequest $request, GetStatsAction $action, int $store): JsonResponse
    {
        $this->authorize('viewStats', [DashboardAuthorization::class, $this->currentStore()]);
        $dto = GetStatsDTO::fromRequest($request, $store);
        $stats = $action->execute($dto);

        return $this->success(
            new StoreStatsResource($stats),
            __('admin.dashboard_stats_fetched')
        );
    }

    public function recentOrders(GetRecentOrdersRequest $request, GetRecentOrdersAction $action, int $store): JsonResponse
    {
        $this->authorize('viewRecentOrders', [DashboardAuthorization::class, $this->currentStore()]);
        $dto = GetRecentOrdersDTO::fromRequest($request, $store);
        $orders = $action->execute($dto);

        return $this->success(
            RecentOrderResource::collection($orders),
            __('admin.recent_orders_fetched')
        );
    }

    public function topProducts(GetTopProductsRequest $request, GetTopProductsAction $action, int $store): JsonResponse
    {
        $this->authorize('viewTopProducts', [DashboardAuthorization::class, $this->currentStore()]);
        $dto = GetTopProductsDTO::fromRequest($request, $store);
        $products = $action->execute($dto);

        return $this->success(
            TopProductResource::collection($products),
            __('admin.top_products_fetched')
        );
    }
}
