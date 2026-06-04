<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create all permissions from PermissionEnum
        $permissions = [
            PermissionEnum::USER_VIEW,
            PermissionEnum::USER_BLOCK,
            PermissionEnum::USER_DELETE,
            PermissionEnum::USER_RESTORE,
            PermissionEnum::PRODUCT_VIEW,
            PermissionEnum::PRODUCT_CREATE,
            PermissionEnum::PRODUCT_UPDATE,
            PermissionEnum::PRODUCT_DELETE,
            PermissionEnum::PRODUCT_RESTORE,
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::ORDER_UPDATE_STATUS,
            PermissionEnum::ORDER_CANCEL,
            PermissionEnum::ORDER_REFUND,
            PermissionEnum::STORE_UPDATE,
            PermissionEnum::STORE_DELETE,
            PermissionEnum::STORE_VIEW,
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::CATEGORY_CREATE,
            PermissionEnum::CATEGORY_UPDATE,
            PermissionEnum::CATEGORY_DELETE,
            PermissionEnum::CATEGORY_RESTORE,
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::BRAND_CREATE,
            PermissionEnum::BRAND_UPDATE,
            PermissionEnum::BRAND_DELETE,
            PermissionEnum::BRAND_RESTORE,
            PermissionEnum::TAG_VIEW,
            PermissionEnum::TAG_CREATE,
            PermissionEnum::TAG_UPDATE,
            PermissionEnum::TAG_DELETE,
            PermissionEnum::CMS_DOC_VIEW,
            PermissionEnum::CMS_DOC_CREATE,
            PermissionEnum::CMS_DOC_UPDATE,
            PermissionEnum::CMS_DOC_DELETE,
            PermissionEnum::CMS_DOC_PUBLISH,
            PermissionEnum::CMS_BLOG_VIEW,
            PermissionEnum::CMS_BLOG_CREATE,
            PermissionEnum::CMS_BLOG_UPDATE,
            PermissionEnum::CMS_BLOG_DELETE,
            PermissionEnum::CMS_BLOG_PUBLISH,
            PermissionEnum::CMS_PAGE_VIEW,
            PermissionEnum::CMS_PAGE_CREATE,
            PermissionEnum::CMS_PAGE_UPDATE,
            PermissionEnum::CMS_PAGE_DELETE,
            PermissionEnum::CMS_PAGE_PUBLISH,
            PermissionEnum::MARKETING_PLATFORM_VIEW,
            PermissionEnum::MARKETING_PLATFORM_CREATE,
            PermissionEnum::MARKETING_PLATFORM_UPDATE,
            PermissionEnum::MARKETING_PLATFORM_DELETE,
            PermissionEnum::MARKETING_PLATFORM_PUBLISH,
            PermissionEnum::MARKETING_STORE_VIEW,
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
            PermissionEnum::MARKETING_STORE_DELETE,
            PermissionEnum::MARKETING_STORE_PUBLISH,
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create all roles from RoleEnum
        $superAdmin = Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value]);
        $storeAdmin = Role::firstOrCreate(['name' => RoleEnum::STORE_ADMIN->value]);
        $staff = Role::firstOrCreate(['name' => RoleEnum::STAFF->value]);
        $customer = Role::firstOrCreate(['name' => RoleEnum::CUSTOMER->value]);

        // Assign permissions to super_admin (ALL permissions)
        $superAdmin->syncPermissions([
            PermissionEnum::USER_VIEW,
            PermissionEnum::USER_BLOCK,
            PermissionEnum::USER_DELETE,
            PermissionEnum::USER_RESTORE,
            PermissionEnum::PRODUCT_VIEW,
            PermissionEnum::PRODUCT_CREATE,
            PermissionEnum::PRODUCT_UPDATE,
            PermissionEnum::PRODUCT_DELETE,
            PermissionEnum::PRODUCT_RESTORE,
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::ORDER_UPDATE_STATUS,
            PermissionEnum::ORDER_CANCEL,
            PermissionEnum::ORDER_REFUND,
            PermissionEnum::STORE_UPDATE,
            PermissionEnum::STORE_DELETE,
            PermissionEnum::STORE_VIEW,
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::CATEGORY_CREATE,
            PermissionEnum::CATEGORY_UPDATE,
            PermissionEnum::CATEGORY_DELETE,
            PermissionEnum::CATEGORY_RESTORE,
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::BRAND_CREATE,
            PermissionEnum::BRAND_UPDATE,
            PermissionEnum::BRAND_DELETE,
            PermissionEnum::BRAND_RESTORE,
            PermissionEnum::TAG_VIEW,
            PermissionEnum::TAG_CREATE,
            PermissionEnum::TAG_UPDATE,
            PermissionEnum::TAG_DELETE,
            PermissionEnum::CMS_DOC_VIEW,
            PermissionEnum::CMS_DOC_CREATE,
            PermissionEnum::CMS_DOC_UPDATE,
            PermissionEnum::CMS_DOC_DELETE,
            PermissionEnum::CMS_DOC_PUBLISH,
            PermissionEnum::CMS_BLOG_VIEW,
            PermissionEnum::CMS_BLOG_CREATE,
            PermissionEnum::CMS_BLOG_UPDATE,
            PermissionEnum::CMS_BLOG_DELETE,
            PermissionEnum::CMS_BLOG_PUBLISH,
            PermissionEnum::CMS_PAGE_VIEW,
            PermissionEnum::CMS_PAGE_CREATE,
            PermissionEnum::CMS_PAGE_UPDATE,
            PermissionEnum::CMS_PAGE_DELETE,
            PermissionEnum::CMS_PAGE_PUBLISH,
            PermissionEnum::MARKETING_PLATFORM_VIEW,
            PermissionEnum::MARKETING_PLATFORM_CREATE,
            PermissionEnum::MARKETING_PLATFORM_UPDATE,
            PermissionEnum::MARKETING_PLATFORM_DELETE,
            PermissionEnum::MARKETING_PLATFORM_PUBLISH,
            PermissionEnum::MARKETING_STORE_VIEW,
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
            PermissionEnum::MARKETING_STORE_DELETE,
            PermissionEnum::MARKETING_STORE_PUBLISH,
        ]);

        // Assign permissions to store_admin
        $storeAdmin->syncPermissions([
            PermissionEnum::USER_VIEW,
            PermissionEnum::USER_BLOCK,
            PermissionEnum::USER_DELETE,
            PermissionEnum::USER_RESTORE,
            PermissionEnum::PRODUCT_VIEW,
            PermissionEnum::PRODUCT_CREATE,
            PermissionEnum::PRODUCT_UPDATE,
            PermissionEnum::PRODUCT_DELETE,
            PermissionEnum::PRODUCT_RESTORE,
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::ORDER_UPDATE_STATUS,
            PermissionEnum::ORDER_CANCEL,
            PermissionEnum::ORDER_REFUND,
            PermissionEnum::STORE_UPDATE,
            PermissionEnum::STORE_VIEW,
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::CATEGORY_CREATE,
            PermissionEnum::CATEGORY_UPDATE,
            PermissionEnum::CATEGORY_DELETE,
            PermissionEnum::CATEGORY_RESTORE,
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::BRAND_CREATE,
            PermissionEnum::BRAND_UPDATE,
            PermissionEnum::BRAND_DELETE,
            PermissionEnum::BRAND_RESTORE,
            PermissionEnum::TAG_VIEW,
            PermissionEnum::TAG_CREATE,
            PermissionEnum::TAG_UPDATE,
            PermissionEnum::TAG_DELETE,
            PermissionEnum::MARKETING_STORE_VIEW,
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
            PermissionEnum::MARKETING_STORE_DELETE,
            PermissionEnum::MARKETING_STORE_PUBLISH,
        ]);

        // Assign permissions to staff
        $staff->syncPermissions([
            PermissionEnum::USER_VIEW,
            PermissionEnum::PRODUCT_VIEW,
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::TAG_VIEW,
        ]);

        // customer gets no permissions (role exists but no permissions assigned)
    }
}
