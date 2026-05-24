<?php

declare(strict_types=1);

namespace App\Services\Auth\Permission;

final class PermissionTransformer
{
    /**
     * @param string[] $permissions
     * @return array<string, bool>
     */
    public static function toFrontendFlags(array $permissions): array
    {
        $flags = [];
        $permissionSet = array_flip($permissions);

        // Map granular permissions to frontend capability flags
        $flags['canViewDashboard'] = isset($permissionSet['dashboard.view']);
        
        $flags['canManageProducts'] = isset($permissionSet['product.create']) || 
                                     isset($permissionSet['product.update']) || 
                                     isset($permissionSet['product.delete']);
        $flags['canViewProducts'] = isset($permissionSet['product.view']) || $flags['canManageProducts'];

        $flags['canManageCategories'] = isset($permissionSet['category.create']) || 
                                        isset($permissionSet['category.update']) || 
                                        isset($permissionSet['category.delete']);
        $flags['canViewCategories'] = isset($permissionSet['category.view']) || $flags['canManageCategories'];

        $flags['canManageBrands'] = isset($permissionSet['brand.create']) || 
                                   isset($permissionSet['brand.update']) || 
                                   isset($permissionSet['brand.delete']);
        $flags['canViewBrands'] = isset($permissionSet['brand.view']) || $flags['canManageBrands'];

        $flags['canManageOrders'] = isset($permissionSet['order.update_status']) || 
                                   isset($permissionSet['order.cancel']) || 
                                   isset($permissionSet['order.refund']);
        $flags['canViewOrders'] = isset($permissionSet['order.view']) || $flags['canManageOrders'];

        $flags['canManageCustomers'] = isset($permissionSet['user.block']) || 
                                      isset($permissionSet['user.delete']);
        $flags['canViewCustomers'] = isset($permissionSet['user.view']) || $flags['canManageCustomers'];
        
        $flags['canManageStore'] = isset($permissionSet['store.update']);

        return $flags;
    }
}
