<?php

declare(strict_types=1);

namespace App\Services\Auth\Policy;

use App\Policies\AddressPolicy;
use App\Policies\BlogPostPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\MembershipPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StorePolicy;
use App\Policies\TagPolicy;

class PolicyCapabilityCatalog
{
    /**
     * @param string[] $middleware
     */
    public function resolve(?string $policyClass, ?string $ability, array $middleware = []): ?string
    {
        $routeCapability = $this->resolveFromMiddleware($middleware);

        if ($routeCapability !== null) {
            return $routeCapability;
        }

        if ($policyClass === null || $ability === null) {
            return null;
        }

        return match ($policyClass) {
            StorePolicy::class => [
                'viewAny' => 'store.view',
                'view' => 'store.view',
                'create' => 'store.create',
                'update' => 'store.update',
                'delete' => 'store.delete',
            ][$ability] ?? null,
            ProductPolicy::class => [
                'viewAny' => 'product.view',
                'view' => 'product.view',
                'create' => 'product.create',
                'update' => 'product.update',
                'delete' => 'product.delete',
            ][$ability] ?? null,
            OrderPolicy::class => [
                'view' => 'order.view',
                'update' => 'order.update_status',
                'cancel' => 'order.cancel',
            ][$ability] ?? null,
            MembershipPolicy::class => [
                'viewAny' => 'membership.view',
                'view' => 'membership.view',
                'create' => 'user.create',
                'update' => 'membership.update',
                'delete' => 'membership.revoke',
            ][$ability] ?? null,
            AddressPolicy::class => [
                'view' => 'address.view',
                'update' => 'address.update',
                'delete' => 'address.delete',
            ][$ability] ?? null,
            PaymentMethodPolicy::class => [
                'update' => 'payment_method.update',
                'delete' => 'payment_method.delete',
            ][$ability] ?? null,
            BlogPostPolicy::class => [
                'viewAny' => 'cms.blog.view',
                'view' => 'cms.blog.view',
                'create' => 'cms.blog.create',
                'update' => 'cms.blog.update',
                'delete' => 'cms.blog.delete',
                'publish' => 'cms.blog.publish',
                'unpublish' => 'cms.blog.unpublish',
                'schedule' => 'cms.blog.schedule',
            ][$ability] ?? null,
            BrandPolicy::class => [
                'viewAny' => 'brand.view',
                'view' => 'brand.view',
                'create' => 'brand.create',
                'update' => 'brand.update',
                'delete' => 'brand.delete',
                'restore' => 'brand.restore',
            ][$ability] ?? null,
            CategoryPolicy::class => [
                'viewAny' => 'category.view',
                'view' => 'category.view',
                'create' => 'category.create',
                'update' => 'category.update',
                'delete' => 'category.delete',
                'restore' => 'category.restore',
            ][$ability] ?? null,
            TagPolicy::class => [
                'viewAny' => 'tag.view',
                'view' => 'tag.view',
                'create' => 'tag.create',
                'update' => 'tag.update',
                'delete' => 'tag.delete',
            ][$ability] ?? null,
            DashboardPolicy::class => [
                'viewStats' => 'dashboard.view',
                'viewRecentOrders' => 'dashboard.view',
                'viewTopProducts' => 'dashboard.view',
            ][$ability] ?? null,
            default => null,
        };
    }

    /**
     * @param string[] $middleware
     */
    public function resolveFromMiddleware(array $middleware): ?string
    {
        foreach ($middleware as $entry) {
            if (!str_starts_with($entry, 'permission:')) {
                continue;
            }

            return (string) str($entry)->after('permission:');
        }

        return null;
    }
}
