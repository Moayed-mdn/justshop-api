<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformDashboardStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'totalUsers' => $this->resource['totalUsers'],
            'activeUsers' => $this->resource['activeUsers'],
            'totalStores' => $this->resource['totalStores'],
            'activeStores' => $this->resource['activeStores'],
            'pendingStores' => $this->resource['pendingStores'],
            'suspendedStores' => $this->resource['suspendedStores'],
            'totalRevenue' => $this->resource['totalRevenue'],
            'revenueThisMonth' => $this->resource['revenueThisMonth'],
            'totalLeads' => $this->resource['totalLeads'],
            'totalOrders' => $this->resource['totalOrders'],
            'ordersThisMonth' => $this->resource['ordersThisMonth'],
            'pendingOrders' => $this->resource['pendingOrders'],
            'usersTrend' => $this->resource['usersTrend'],
            'storesTrend' => $this->resource['storesTrend'],
            'revenueTrend' => $this->resource['revenueTrend'],
            'ordersTrend' => $this->resource['ordersTrend'],
            'leadsTrend' => $this->resource['leadsTrend'],
            'totalSubscriptions' => $this->resource['totalSubscriptions'],
            'activeSubscriptions' => $this->resource['activeSubscriptions'],
            'trialingSubscriptions' => $this->resource['trialingSubscriptions'],
            'pastDueSubscriptions' => $this->resource['pastDueSubscriptions'],
            'canceledSubscriptions' => $this->resource['canceledSubscriptions'],
            'subscriptionsThisMonth' => $this->resource['subscriptionsThisMonth'],
            'subscriptionsTrend' => $this->resource['subscriptionsTrend'],
            'totalSubscriptionRevenue' => $this->resource['totalSubscriptionRevenue'],
            'subscriptionRevenueThisMonth' => $this->resource['subscriptionRevenueThisMonth'],
            'subscriptionRevenueTrend' => $this->resource['subscriptionRevenueTrend'],
        ];
    }
}
