<?php

declare(strict_types=1);

namespace App\Actions\Platform\Dashboard;

use App\DTOs\Platform\Dashboard\GetPlatformDashboardStatsDTO;
use App\Repositories\Platform\PlatformDashboardRepository;
use App\Services\Platform\TrendCalculatorService;

class GetPlatformDashboardStatsAction
{
    public function __construct(
        private readonly PlatformDashboardRepository $repository,
        private readonly TrendCalculatorService $trendCalculator,
    ) {}

    public function execute(GetPlatformDashboardStatsDTO $dto): array
    {
        $thirtyDaysAgo = now()->subDays($dto->trendDays);
        $sixtyDaysAgo = now()->subDays($dto->trendDays * 2);

        // Get totals
        $totalUsers = $this->repository->getTotalUsers();
        $totalStores = $this->repository->getTotalStores();
        $totalLeads = $this->repository->getTotalLeads();
        $totalOrders = $this->repository->getTotalOrders();
        $pendingOrders = $this->repository->getPendingOrders();
        $totalRevenue = $this->repository->getTotalRevenue();

        // Get active counts
        $activeUsers = $this->repository->getActiveUsersCount($thirtyDaysAgo);
        $activeStores = $this->repository->getActiveStoresCount();
        $pendingStores = $this->repository->getPendingStoresCount();
        $suspendedStores = $this->repository->getSuspendedStoresCount();

        // Calculate trends for users
        $usersThisMonth = $this->repository->getUsersCreatedSince($thirtyDaysAgo);
        $usersPreviousMonth = $this->repository->getUsersCreatedBetween($sixtyDaysAgo, $thirtyDaysAgo);
        $usersTrend = $this->trendCalculator->calculateTrend($usersThisMonth, $usersPreviousMonth);

        // Calculate trends for stores
        $storesThisMonth = $this->repository->getStoresCreatedSince($thirtyDaysAgo);
        $storesPreviousMonth = $this->repository->getStoresCreatedBetween($sixtyDaysAgo, $thirtyDaysAgo);
        $storesTrend = $this->trendCalculator->calculateTrend($storesThisMonth, $storesPreviousMonth);

        // Calculate trends for orders
        $ordersThisMonth = $this->repository->getOrdersCreatedSince($thirtyDaysAgo);
        $ordersPreviousMonth = $this->repository->getOrdersCreatedBetween($sixtyDaysAgo, $thirtyDaysAgo);
        $ordersTrend = $this->trendCalculator->calculateTrend($ordersThisMonth, $ordersPreviousMonth);

        // Calculate trends for revenue
        $revenueThisMonth = $this->repository->getRevenueCreatedSince($thirtyDaysAgo);
        $revenuePreviousMonth = $this->repository->getRevenueCreatedBetween($sixtyDaysAgo, $thirtyDaysAgo);
        $revenueTrend = $this->trendCalculator->calculateRevenueTrend($revenueThisMonth, $revenuePreviousMonth);

        // Calculate trends for leads
        $leadsThisMonth = $this->repository->getLeadsCreatedSince($thirtyDaysAgo);
        $leadsPreviousMonth = $this->repository->getLeadsCreatedBetween($sixtyDaysAgo, $thirtyDaysAgo);
        $leadsTrend = $this->trendCalculator->calculateTrend($leadsThisMonth, $leadsPreviousMonth);

        // ── Subscriptions & platform revenue (SaaS income — separate from
        // store order GMV above; see PlatformDashboardRepository docblocks) ──
        $totalSubscriptions = $this->repository->getTotalSubscriptions();
        $activeSubscriptions = $this->repository->getActiveSubscriptionsCount();
        $trialingSubscriptions = $this->repository->getTrialingSubscriptionsCount();
        $pastDueSubscriptions = $this->repository->getPastDueSubscriptionsCount();
        $canceledSubscriptions = $this->repository->getCanceledSubscriptionsCount();

        $subscriptionsThisMonth = $this->repository->getSubscriptionsCreatedSince($thirtyDaysAgo);
        $subscriptionsPreviousMonth = $this->repository->getSubscriptionsCreatedBetween($sixtyDaysAgo, $thirtyDaysAgo);
        $subscriptionsTrend = $this->trendCalculator->calculateTrend($subscriptionsThisMonth, $subscriptionsPreviousMonth);

        $totalSubscriptionRevenue = $this->repository->getTotalSubscriptionRevenueCents() / 100;

        $subscriptionRevenueThisMonth = $this->repository->getSubscriptionRevenueCentsCreatedSince($thirtyDaysAgo) / 100;
        $subscriptionRevenuePreviousMonth = $this->repository->getSubscriptionRevenueCentsCreatedBetween($sixtyDaysAgo, $thirtyDaysAgo) / 100;
        $subscriptionRevenueTrend = $this->trendCalculator->calculateRevenueTrend(
            $subscriptionRevenueThisMonth,
            $subscriptionRevenuePreviousMonth
        );

        return [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'totalStores' => $totalStores,
            'activeStores' => $activeStores,
            'pendingStores' => $pendingStores,
            'suspendedStores' => $suspendedStores,
            'totalRevenue' => $totalRevenue,
            'revenueThisMonth' => $revenueThisMonth,
            'totalLeads' => $totalLeads,
            'totalOrders' => $totalOrders,
            'ordersThisMonth' => $ordersThisMonth,
            'pendingOrders' => $pendingOrders,
            'usersTrend' => $usersTrend,
            'storesTrend' => $storesTrend,
            'revenueTrend' => $revenueTrend,
            'ordersTrend' => $ordersTrend,
            'leadsTrend' => $leadsTrend,
            'totalSubscriptions' => $totalSubscriptions,
            'activeSubscriptions' => $activeSubscriptions,
            'trialingSubscriptions' => $trialingSubscriptions,
            'pastDueSubscriptions' => $pastDueSubscriptions,
            'canceledSubscriptions' => $canceledSubscriptions,
            'subscriptionsThisMonth' => $subscriptionsThisMonth,
            'subscriptionsTrend' => $subscriptionsTrend,
            'totalSubscriptionRevenue' => $totalSubscriptionRevenue,
            'subscriptionRevenueThisMonth' => $subscriptionRevenueThisMonth,
            'subscriptionRevenueTrend' => $subscriptionRevenueTrend,
        ];
    }
}
