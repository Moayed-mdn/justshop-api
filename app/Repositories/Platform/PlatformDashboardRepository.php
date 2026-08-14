<?php

declare(strict_types=1);

namespace App\Repositories\Platform;

use App\Enums\Billing\InvoiceStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformDashboardRepository
{
    public function getTotalUsers(): int
    {
        return User::count();
    }

    public function getTotalStores(): int
    {
        return Store::count();
    }

    public function getTotalLeads(): int
    {
        return Lead::count();
    }

    public function getTotalOrders(): int
    {
        return Order::count();
    }

    public function getPendingOrders(): int
    {
        return Order::where('status', OrderStatusEnum::PENDING)->count();
    }

    /**
     * Total gross merchandise value (GMV) across all stores' paid orders.
     *
     * IMPORTANT: this is customer→merchant order revenue flowing through
     * stores, NOT platform income — since Stripe Connect, most of this
     * settles directly to merchants' connected accounts, minus the
     * platform's application fee. For actual platform (SaaS subscription)
     * revenue, see getTotalSubscriptionRevenueCents().
     */
    public function getTotalRevenue(): float
    {
        return (float) Order::whereIn('status', [
            OrderStatusEnum::PROCESSING,
            OrderStatusEnum::SHIPPED,
            OrderStatusEnum::DELIVERED,
        ])->sum('total');
    }

    public function getUsersCreatedSince(Carbon $date): int
    {
        return User::where('created_at', '>=', $date)->count();
    }

    public function getUsersCreatedBetween(Carbon $start, Carbon $end): int
    {
        return User::where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();
    }

    public function getStoresCreatedSince(Carbon $date): int
    {
        return Store::where('created_at', '>=', $date)->count();
    }

    public function getStoresCreatedBetween(Carbon $start, Carbon $end): int
    {
        return Store::where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();
    }

    public function getOrdersCreatedSince(Carbon $date): int
    {
        return Order::where('created_at', '>=', $date)->count();
    }

    public function getOrdersCreatedBetween(Carbon $start, Carbon $end): int
    {
        return Order::where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();
    }

    public function getRevenueCreatedSince(Carbon $date): float
    {
        return (float) Order::where('created_at', '>=', $date)
            ->whereIn('status', [
                OrderStatusEnum::PROCESSING,
                OrderStatusEnum::SHIPPED,
                OrderStatusEnum::DELIVERED,
            ])
            ->sum('total');
    }

    public function getRevenueCreatedBetween(Carbon $start, Carbon $end): float
    {
        return (float) Order::where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereIn('status', [
                OrderStatusEnum::PROCESSING,
                OrderStatusEnum::SHIPPED,
                OrderStatusEnum::DELIVERED,
            ])
            ->sum('total');
    }

    public function getLeadsCreatedSince(Carbon $date): int
    {
        return Lead::where('created_at', '>=', $date)->count();
    }

    public function getLeadsCreatedBetween(Carbon $start, Carbon $end): int
    {
        return Lead::where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();
    }

    public function getActiveUsersCount(Carbon $since): int
    {
        return Order::where('created_at', '>=', $since)
            ->distinct('user_id')
            ->count('user_id');
    }

    public function getActiveStoresCount(): int
    {
        return Store::where('status', StoreStatusEnum::ACTIVE)->count();
    }

    public function getPendingStoresCount(): int
    {
        return Store::where('status', StoreStatusEnum::PENDING_SETUP)->count();
    }

    public function getSuspendedStoresCount(): int
    {
        return Store::where('status', StoreStatusEnum::SUSPENDED)->count();
    }

    // ── Subscriptions & Platform Revenue ──────────────────────────
    // These reflect the platform's actual SaaS income (merchants paying
    // for their subscription plan) — a completely separate money flow
    // from store order GMV above. See Subscription/Invoice/BillingAccount.

    public function getTotalSubscriptions(): int
    {
        return Subscription::count();
    }

    public function getActiveSubscriptionsCount(): int
    {
        return Subscription::where('status', SubscriptionStatusEnum::ACTIVE)->count();
    }

    public function getTrialingSubscriptionsCount(): int
    {
        return Subscription::where('status', SubscriptionStatusEnum::TRIALING)->count();
    }

    /**
     * Past due + grace period — subscriptions with a payment problem that
     * haven't been canceled or expired yet. Grouped together since both
     * represent "at risk" revenue a platform admin would want to see as one
     * number, mirroring how store statuses are bucketed above.
     */
    public function getPastDueSubscriptionsCount(): int
    {
        return Subscription::whereIn('status', [
            SubscriptionStatusEnum::PAST_DUE,
            SubscriptionStatusEnum::GRACE_PERIOD,
        ])->count();
    }

    public function getCanceledSubscriptionsCount(): int
    {
        return Subscription::where('status', SubscriptionStatusEnum::CANCELED)->count();
    }

    public function getSubscriptionsCreatedSince(Carbon $date): int
    {
        return Subscription::where('created_at', '>=', $date)->count();
    }

    public function getSubscriptionsCreatedBetween(Carbon $start, Carbon $end): int
    {
        return Subscription::where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();
    }

    /**
     * Actual platform subscription revenue collected, in cents.
     *
     * Uses Invoice.amount_paid_cents on PAID invoices — actual collected
     * cash, not the subscription's nominal plan price (which wouldn't
     * reflect prorations, partial payments, or failed charges).
     *
     * NOTE: sums across all currencies without conversion. This mirrors
     * getTotalRevenue()'s existing behavior of summing Order.total without
     * currency grouping — acceptable while the platform operates in a
     * single billing currency, but revisit if multi-currency billing is
     * ever introduced.
     */
    public function getTotalSubscriptionRevenueCents(): int
    {
        return (int) Invoice::where('status', InvoiceStatusEnum::PAID)->sum('amount_paid_cents');
    }

    public function getSubscriptionRevenueCentsCreatedSince(Carbon $date): int
    {
        return (int) Invoice::where('status', InvoiceStatusEnum::PAID)
            ->where('paid_at', '>=', $date)
            ->sum('amount_paid_cents');
    }

    public function getSubscriptionRevenueCentsCreatedBetween(Carbon $start, Carbon $end): int
    {
        return (int) Invoice::where('status', InvoiceStatusEnum::PAID)
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end)
            ->sum('amount_paid_cents');
    }

    public function getBlogStats(): array
    {
        $published = DB::table('blog_posts')->where('is_published', true)->count();
        $draft = DB::table('blog_posts')->where('is_published', false)->whereNull('deleted_at')->count();
        $archived = DB::table('blog_posts')->whereNotNull('deleted_at')->count();

        return [
            'total' => $published + $draft + $archived,
            'published' => $published,
            'draft' => $draft,
            'archived' => $archived,
        ];
    }

    public function getMarketingPagesStats(): array
    {
        $pageStats = DB::table('platform_marketing_pages')
            ->select('status', DB::raw('count(*) as total'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'total' => array_sum($pageStats),
            'published' => $pageStats['published'] ?? 0,
            'draft' => $pageStats['draft'] ?? 0,
            'archived' => $pageStats['archived'] ?? 0,
        ];
    }

    public function getDocumentationStats(): array
    {
        $published = DB::table('cms_documents')->where('is_published', true)->count();
        $draft = DB::table('cms_documents')->where('is_published', false)->whereNull('deleted_at')->count();
        $archived = DB::table('cms_documents')->whereNotNull('deleted_at')->count();

        return [
            'total' => $published + $draft + $archived,
            'published' => $published,
            'draft' => $draft,
            'archived' => $archived,
        ];
    }
}
